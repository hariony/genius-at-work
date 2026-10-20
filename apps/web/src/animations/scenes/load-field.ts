/**
 * Visuel du hero « sous charge » : des requêtes simulées traversent les quatre couches de la
 * stack (Interface → API → Métier → Données). Chaque nœud traite sa file à son rythme ; une
 * requête choisit le moins chargé de deux nœuds tirés au hasard (« power of two choices »).
 * Le pointeur envoie un surplus de trafic que le système absorbe.
 *
 * Canvas autonome, sans GSAP : monté par HeroLoadField.astro, y compris en mouvement réduit
 * (une image fixe de l'état d'équilibre, sans interaction). En pause hors écran ou onglet masqué.
 */

export interface LoadFieldOptions {
  /** Libellés des couches, de l'entrée vers les données. */
  layers: string[];
  /** Zone qui reçoit le pointeur (le hero entier). */
  pointerArea: HTMLElement;
  reduced: boolean;
  /** Nombre de requêtes traitées depuis le chargement. */
  onCount?: (total: number) => void;
}

interface Node {
  layer: number;
  x: number;
  y: number;
  queue: number;
  /** Requêtes en route vers ce nœud : comptées pour le choix, sinon tous visent le même. */
  incoming: number;
  /** Progression du traitement de la requête en tête de file. */
  work: number;
  heat: number;
}

interface Request {
  x: number;
  y: number;
  fromX: number;
  fromY: number;
  target: Node | undefined;
  t: number;
  duration: number;
  /** Arrivée au nœud cible : en file, représentée par le remplissage du nœud. */
  queued: boolean;
}

/** Nœuds par couche et requêtes traitées par seconde et par nœud. */
const NODES_PER_LAYER = [3, 3, 4, 2];
const SERVICE_RATE = [12, 10, 6, 12];
const QUEUE_CAPACITY = 8;
const BASE_RATE = 9;
const MAX_POINTER_RATE = 45;
const MAX_REQUESTS = 700;
const TAP_BURST = 36;

export function mountLoadField(canvas: HTMLCanvasElement, options: LoadFieldOptions): () => void {
  const ctx = canvas.getContext('2d');
  if (!ctx) return () => undefined;

  const layerCount = Math.min(options.layers.length, NODES_PER_LAYER.length);
  const nodes: Node[][] = Array.from({ length: layerCount }, (_, layer) =>
    Array.from({ length: NODES_PER_LAYER[layer] ?? 2 }, () => ({
      layer,
      x: 0,
      y: 0,
      queue: 0,
      incoming: 0,
      work: 0,
      heat: 0,
    })),
  );
  const requests: Request[] = [];
  let width = 0;
  let height = 0;
  let top = 0;
  let nodeW = 0;
  let nodeH = 0;
  let total = 0;
  let spawnDebt = 0;
  let pointerRate = 0;
  let pointer: { x: number; y: number } | undefined;
  let colors = readColors(canvas);

  // ---------- Mise en page ----------

  const layout = () => {
    const rect = canvas.getBoundingClientRect();
    const dpr = Math.min(window.devicePixelRatio || 1, 2);
    width = rect.width;
    height = rect.height;
    canvas.width = Math.round(width * dpr);
    canvas.height = Math.round(height * dpr);
    ctx.setTransform(dpr, 0, 0, dpr, 0, 0);

    top = 34;
    nodeW = Math.min(64, width / layerCount / 2.2);
    nodeH = Math.max(14, Math.min(22, height / 22));
    const left = nodeW / 2 + 8;
    const step = (width - nodeW - 16) / Math.max(1, layerCount - 1);
    nodes.forEach((column, layer) => {
      const gap = (height - top - 12) / column.length;
      column.forEach((node, i) => {
        node.x = left + layer * step;
        node.y = top + gap * (i + 0.5);
      });
    });
    colors = readColors(canvas);
  };

  // ---------- Simulation ----------

  const pick = (layer: number): Node | undefined => {
    const column = nodes[layer] ?? [];
    const a = column[Math.floor(Math.random() * column.length)];
    const b = column[Math.floor(Math.random() * column.length)];
    if (!a || !b) return a ?? b;
    return a.queue + a.incoming <= b.queue + b.incoming ? a : b;
  };

  const send = (request: Request, target: Node | undefined) => {
    request.fromX = request.x;
    request.fromY = request.y;
    request.target = target;
    request.t = 0;
    request.queued = false;
    const toX = target ? target.x : width + 8;
    const toY = target ? target.y : request.y;
    request.duration = 0.2 + Math.hypot(toX - request.x, toY - request.y) / 900;
    if (target) target.incoming++;
  };

  const spawn = (x?: number, y?: number) => {
    if (requests.length >= MAX_REQUESTS || layerCount === 0) return;
    const request: Request = {
      x: x ?? -8,
      y: y ?? top + Math.random() * (height - top),
      fromX: 0,
      fromY: 0,
      target: undefined,
      t: 0,
      duration: 1,
      queued: false,
    };
    send(request, pick(0));
    requests.push(request);
  };

  const step = (dt: number) => {
    spawnDebt += (BASE_RATE + pointerRate) * dt;
    pointerRate *= Math.exp(-dt * 2.5);
    while (spawnDebt >= 1) {
      spawnDebt--;
      const fromPointer = pointer && Math.random() < pointerRate / (BASE_RATE + pointerRate);
      spawn(fromPointer ? pointer?.x : undefined, fromPointer ? pointer?.y : undefined);
    }

    for (const column of nodes) {
      for (const node of column) {
        if (node.queue > 0) node.work += (SERVICE_RATE[node.layer] ?? 5) * dt;
        node.heat += (node.queue / QUEUE_CAPACITY - node.heat) * Math.min(1, dt * 4);
      }
    }

    for (let i = requests.length - 1; i >= 0; i--) {
      const request = requests[i];
      if (!request) continue;
      const target = request.target;
      if (request.queued) continue;
      request.t = Math.min(1, request.t + dt / request.duration);
      const e = easeInOut(request.t);
      const toX = target ? target.x : width + 8;
      const toY = target ? target.y : request.fromY;
      request.x = request.fromX + (toX - request.fromX) * e;
      request.y = request.fromY + (toY - request.fromY) * e;
      if (request.t < 1) continue;
      if (!target) {
        requests.splice(i, 1);
        total++;
        continue;
      }
      target.incoming--;
      target.queue++;
      request.queued = true;
    }

    // Chaque nœud libère les requêtes traitées, dans l'ordre d'arrivée.
    for (const column of nodes) {
      for (const node of column) {
        while (node.work >= 1 && node.queue > 0) {
          node.work--;
          node.queue--;
          const request = requests.find((r) => r.queued && r.target === node);
          if (!request) break;
          const next = node.layer + 1 < layerCount ? pick(node.layer + 1) : undefined;
          send(request, next);
        }
        if (node.queue === 0) node.work = 0;
      }
    }
  };

  // ---------- Rendu ----------

  const draw = () => {
    ctx.clearRect(0, 0, width, height);

    // Liaisons entre couches voisines.
    ctx.lineWidth = 1;
    ctx.strokeStyle = colors.border;
    ctx.beginPath();
    for (let layer = 0; layer < layerCount - 1; layer++) {
      for (const a of nodes[layer] ?? []) {
        for (const b of nodes[layer + 1] ?? []) {
          ctx.moveTo(a.x + nodeW / 2, a.y);
          ctx.lineTo(b.x - nodeW / 2, b.y);
        }
      }
    }
    ctx.stroke();

    // Libellés des couches.
    ctx.font = `500 11px ${colors.mono}`;
    ctx.textAlign = 'center';
    ctx.textBaseline = 'top';
    ctx.fillStyle = colors.muted;
    nodes.forEach((column, layer) => {
      const label = options.layers[layer] ?? '';
      if (column[0]) ctx.fillText(label.toUpperCase(), column[0].x, 4);
    });

    // Nœuds : contour, puis remplissage selon la charge (chaud au-delà de 75 %).
    for (const column of nodes) {
      for (const node of column) {
        const x = node.x - nodeW / 2;
        const y = node.y - nodeH / 2;
        const load = Math.min(1, node.heat);
        const hot = load > 0.75;
        ctx.fillStyle = colors.bg;
        roundRect(ctx, x, y, nodeW, nodeH, 4);
        ctx.fill();
        if (load > 0.02) {
          ctx.fillStyle = hot ? colors.hot : colors.signal;
          ctx.globalAlpha = 0.25 + load * 0.6;
          roundRect(ctx, x + 2, y + 2, Math.max(2, (nodeW - 4) * load), nodeH - 4, 2);
          ctx.fill();
          ctx.globalAlpha = 1;
        }
        ctx.strokeStyle = hot ? colors.hot : colors.borderStrong;
        roundRect(ctx, x, y, nodeW, nodeH, 4);
        ctx.stroke();
      }
    }

    // Requêtes en route (les requêtes en file sont représentées par le remplissage).
    ctx.fillStyle = colors.signal;
    ctx.beginPath();
    for (const request of requests) {
      if (request.queued) continue;
      ctx.moveTo(request.x + 1.8, request.y);
      ctx.arc(request.x, request.y, 1.8, 0, Math.PI * 2);
    }
    ctx.fill();
  };

  // ---------- Image fixe (mouvement réduit) ----------

  if (options.reduced) {
    const still = () => {
      layout();
      for (let i = 0; i < 240; i++) step(1 / 60);
      draw();
    };
    still();
    const resize = new ResizeObserver(still);
    resize.observe(canvas);
    return () => resize.disconnect();
  }

  // ---------- Boucle animée ----------

  let frame = 0;
  let last = 0;
  let visible = false;
  let lastEmit = 0;
  let lastPointer: { x: number; y: number; time: number } | undefined;

  const tick = (now: number) => {
    const dt = Math.min(0.05, (now - last) / 1000);
    last = now;
    step(dt);
    draw();
    if (now - lastEmit > 250) {
      lastEmit = now;
      options.onCount?.(total);
    }
    frame = requestAnimationFrame(tick);
  };

  const start = () => {
    if (frame || !visible || document.hidden) return;
    last = performance.now();
    frame = requestAnimationFrame(tick);
  };
  const stop = () => {
    cancelAnimationFrame(frame);
    frame = 0;
  };

  const toLocal = (event: PointerEvent) => {
    const rect = canvas.getBoundingClientRect();
    return {
      x: Math.max(-8, Math.min(event.clientX - rect.left, width * 0.25)),
      y: Math.max(top, Math.min(event.clientY - rect.top, height)),
    };
  };
  const onMove = (event: PointerEvent) => {
    const now = performance.now();
    const position = toLocal(event);
    if (lastPointer) {
      const speed = Math.hypot(event.clientX - lastPointer.x, event.clientY - lastPointer.y);
      const elapsed = Math.max(16, now - lastPointer.time) / 1000;
      pointerRate = Math.min(MAX_POINTER_RATE, pointerRate + (speed / elapsed) * 0.012);
    }
    lastPointer = { x: event.clientX, y: event.clientY, time: now };
    pointer = position;
  };
  const onDown = (event: PointerEvent) => {
    const { x, y } = toLocal(event);
    for (let i = 0; i < TAP_BURST; i++) spawn(x, y + (Math.random() - 0.5) * 40);
  };
  const onVisibility = () => (document.hidden ? stop() : start());

  const io = new IntersectionObserver(([entry]) => {
    visible = Boolean(entry?.isIntersecting);
    if (visible) start();
    else stop();
  });
  const resize = new ResizeObserver(() => {
    layout();
    if (!frame) draw();
  });

  // Le système tourne déjà quand le hero apparaît ; le compteur part de zéro.
  layout();
  for (let i = 0; i < 180; i++) step(1 / 60);
  total = 0;
  io.observe(canvas);
  resize.observe(canvas);
  options.pointerArea.addEventListener('pointermove', onMove, { passive: true });
  options.pointerArea.addEventListener('pointerdown', onDown, { passive: true });
  document.addEventListener('visibilitychange', onVisibility);

  return () => {
    stop();
    io.disconnect();
    resize.disconnect();
    options.pointerArea.removeEventListener('pointermove', onMove);
    options.pointerArea.removeEventListener('pointerdown', onDown);
    document.removeEventListener('visibilitychange', onVisibility);
  };
}

function readColors(el: HTMLElement) {
  const style = getComputedStyle(el);
  const read = (name: string) => style.getPropertyValue(name).trim();
  return {
    bg: read('--surface-bg'),
    muted: read('--surface-fg-muted'),
    border: read('--surface-border'),
    borderStrong: read('--surface-border-strong'),
    signal: read('--surface-highlight'),
    hot: read('--surface-hot'),
    mono: read('--font-mono') || 'monospace',
  };
}

function easeInOut(t: number): number {
  return t < 0.5 ? 2 * t * t : 1 - (-2 * t + 2) ** 2 / 2;
}

function roundRect(
  ctx: CanvasRenderingContext2D,
  x: number,
  y: number,
  w: number,
  h: number,
  r: number,
): void {
  ctx.beginPath();
  ctx.roundRect(x, y, w, h, r);
}
