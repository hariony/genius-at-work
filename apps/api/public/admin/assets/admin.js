// Confort du back-office, facultatif : tout fonctionne sans JavaScript.

// « / » place le curseur dans la recherche.
document.addEventListener('keydown', (event) => {
  if (event.key !== '/' || event.metaKey || event.ctrlKey || event.altKey) return;
  const target = event.target;
  if (target instanceof HTMLElement && target.closest('input, textarea, select, [contenteditable]')) return;
  const search = document.querySelector('[data-search-shortcut]');
  if (!search) return;
  event.preventDefault();
  search.focus();
  search.select();
});

// Les listes déroulantes des filtres s'appliquent dès qu'on les change.
for (const form of document.querySelectorAll('form[data-autosubmit]')) {
  for (const select of form.querySelectorAll('select')) {
    select.addEventListener('change', () => form.requestSubmit());
  }
}

// Aperçu de l'image choisie avant l'envoi (data: : seule source d'image autorisée en plus de 'self').
for (const input of document.querySelectorAll('[data-cover-input]')) {
  const preview = input.closest('form')?.querySelector('[data-cover-preview]');
  input.addEventListener('change', () => {
    const file = input.files?.[0];
    if (!preview || !file || !file.type.startsWith('image/')) return;
    const reader = new FileReader();
    reader.addEventListener('load', () => {
      const image = new Image();
      image.alt = '';
      image.src = String(reader.result);
      const caption = document.createElement('figcaption');
      caption.className = 'muted small';
      caption.textContent = `Nouvelle image : ${file.name} (${Math.round(file.size / 1024)} Ko), envoyée à l’enregistrement.`;
      preview.replaceChildren(image, caption);
    });
    reader.readAsDataURL(file);
  });
}
