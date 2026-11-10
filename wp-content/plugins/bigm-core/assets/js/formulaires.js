// Big M — formulaires Elementor Pro : le champ téléphone d'Elementor refuse les espaces
// (« 06 12 34 56 78 »). On les retire dès que la personne quitte le champ.
(() => {
  document.addEventListener('change', (e) => {
    const champ = e.target;
    if (champ.matches && champ.matches('.elementor-field-type-tel input')) champ.value = champ.value.replace(/\s+/g, '');
  });
})();
