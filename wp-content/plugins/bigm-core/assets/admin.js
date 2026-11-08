/* Big M — admin : sélecteurs de médias, slides, filtre des produits associés. */
(function ($) {
  'use strict';

  // ---------- Sélecteur de média (image, MP4, WebM) ----------
  const resetMedia = (box) => {
    box.find('input[type=hidden]').val('');
    box.find('.bigm-media__preview').empty();
    box.find('.bigm-media__choose').text('Choisir');
    box.find('.bigm-media__remove').prop('hidden', true);
  };

  $(document).on('click', '.bigm-media__choose', function (e) {
    e.preventDefault();
    const box = $(this).closest('.bigm-media');
    const type = String(box.data('type'));
    const frame = wp.media({
      title: box.find('.bigm-media__label').text(),
      button: { text: 'Utiliser ce fichier' },
      library: { type },
      multiple: false,
    });
    frame.on('select', () => {
      const a = frame.state().get('selection').first().toJSON();
      if (a.mime && !a.mime.startsWith(type)) {
        window.alert('Ce champ attend un fichier ' + type + ' ; le fichier choisi est en ' + a.mime + '.');
        return;
      }
      box.find('input[type=hidden]').val(a.id).trigger('change');
      const preview = type === 'image'
        ? $('<img>', { src: a.sizes && a.sizes.thumbnail ? a.sizes.thumbnail.url : a.url, alt: '' })
        : $('<span class="bigm-media__file">').text(a.filename);
      box.find('.bigm-media__preview').empty().append(preview);
      box.find('.bigm-media__choose').text('Remplacer');
      box.find('.bigm-media__remove').prop('hidden', false);
    });
    frame.open();
  });

  $(document).on('click', '.bigm-media__remove', function (e) {
    e.preventDefault();
    resetMedia($(this).closest('.bigm-media'));
  });

  // Après l'ajout d'une catégorie (formulaire en AJAX), vider aussi les médias et les listes.
  $(document).ajaxSuccess((event, xhr, settings) => {
    if (typeof settings.data === 'string' && settings.data.includes('action=add-tag') && settings.data.includes('taxonomy=bigm_categorie')) {
      $('#addtag .bigm-media').each(function () { resetMedia($(this)); });
      $('#addtag select[name="bigm_cat[slider]"]').val('0');
      $('#addtag .bigm-icone-apercu').remove();
    }
  });

  // ---------- Slides ----------
  const list = $('.bigm-slides');
  if (list.length) {
    const tpl = $('#tmpl-bigm-slide').html();
    const now = String(list.data('now'));

    const refresh = () => {
      list.children('.bigm-slide').each(function (i) {
        const li = $(this);
        const debut = li.find('.bigm-slide__debut').val();
        const fin = li.find('.bigm-slide__fin').val();
        let status = 'Visible';
        if (debut && debut > now) status = 'Programmée';
        if (fin && fin <= now) status = 'Terminée';
        li.find('.bigm-slide__status').text((i + 1) + ' · ' + status).attr('data-status', status);
        li.find('.bigm-slide__up').prop('disabled', i === 0);
        li.find('.bigm-slide__down').prop('disabled', i === list.children().length - 1);
      });
    };

    $('.bigm-slides__add').on('click', () => {
      const li = $(tpl.split('__KEY__').join('n' + Date.now()));
      list.append(li);
      refresh();
      li.find('.bigm-slide__type input').first().trigger('focus');
    });

    list.on('click', '.bigm-slide__remove', function () {
      if (window.confirm('Supprimer cette slide ?')) {
        $(this).closest('.bigm-slide').remove();
        refresh();
      }
    });
    list.on('click', '.bigm-slide__up', function () {
      const li = $(this).closest('.bigm-slide');
      li.prev().before(li);
      refresh();
      li.find('.bigm-slide__up').trigger('focus');
    });
    list.on('click', '.bigm-slide__down', function () {
      const li = $(this).closest('.bigm-slide');
      li.next().after(li);
      refresh();
      li.find('.bigm-slide__down').trigger('focus');
    });
    list.on('change', '.bigm-slide__type input', function () {
      $(this).closest('.bigm-slide').attr('data-type', this.value);
    });
    list.on('input', '.bigm-slide__alt', function () {
      $(this).closest('.bigm-slide').find('.bigm-slide__title').text(this.value || 'Nouvelle slide');
    });
    list.on('change input', '.bigm-slide__debut, .bigm-slide__fin', refresh);

    list.sortable({
      handle: '.bigm-slide__handle',
      axis: 'y',
      placeholder: 'bigm-slide--placeholder',
      forcePlaceholderSize: true,
      update: refresh,
    });
    refresh();
  }

  // ---------- Plateformes de commande (Réglages Big M) : ordre par glisser-déposer ----------
  $('.bigm-plateformes tbody').sortable({
    handle: '.bigm-plateformes__poignee',
    axis: 'y',
    update() {
      // Les noms des champs suivent l'ordre affiché : c'est l'ordre enregistré.
      $(this).children('tr').each(function (i) {
        $(this).find('[name^="bigm_reglages[commande_plateformes]"]').each(function () {
          this.name = this.name.replace(/\[commande_plateformes\]\[\d+\]/, '[commande_plateformes][' + i + ']');
        });
      });
    },
  });

  // ---------- Filtre de liste (produits associés) ---------- et liens de commande)
  $(document).on('input', '.bigm-filtre', function () {
    const q = this.value.trim().toLowerCase();
    $($(this).data('filtre')).each(function () {
      $(this).toggle(!q || $(this).text().toLowerCase().includes(q));
    });
  });
})(jQuery);
