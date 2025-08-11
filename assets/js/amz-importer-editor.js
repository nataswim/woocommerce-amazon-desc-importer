jQuery(function($){
  const $btn   = $('#amz-import-desc-editor-btn');
  const $state = $('#amz-import-desc-status');

  function setState(msg, isError=false){
    $state.text(msg).css({color: isError ? '#b32d2e' : '#2271b1'});
  }

  $(document).on('click', '#amz-import-desc-editor-btn', async function(){
    const asin   = $('#_sku').val()?.trim();         // WooCommerce SKU = ASIN
    const postId = $('#post_ID').val();
    if(!asin){
      alert('Renseignez d’abord le SKU (ASIN).');
      return;
    }

    $btn.prop('disabled', true).text('Import en cours…');
    setState('Connexion à Amazon…');

    try{
      const res = await $.post(WADI_IMPORTER.ajax_url, {
        action: 'wadi_import_description_editor',
        nonce: WADI_IMPORTER.nonce,
        post_id: postId,
        asin
      });

      const data = typeof res === 'string' ? JSON.parse(res) : res;
      if(!data.success){
        setState('Échec import : ' + (data.data?.message || 'inconnu'), true);
        return;
      }

      const html = data.data.html || '';
      if(!html){
        setState('Aucun contenu reçu.', true);
        return;
      }

      // Détection Gutenberg vs Classic
      const isGutenberg = !!(window.wp && wp.data && wp.data.select && wp.data.select('core/editor'));

      if (isGutenberg) {
        const current = wp.data.select('core/editor').getEditedPostContent() || '';
        wp.data.dispatch('core/editor').editPost({ content: current + "\n\n" + html });
        setState('Contenu inséré dans l’éditeur (Gutenberg).');
      } else {
        if (typeof tinyMCE !== 'undefined' && tinyMCE.activeEditor && !tinyMCE.activeEditor.isHidden()){
          const editor = tinyMCE.activeEditor;
          editor.setContent(editor.getContent() + "\n\n" + html);
          setState('Contenu inséré dans l’éditeur (Classic).');
        } else {
          // Fallback textarea
          const $txt = $('#content');
          $txt.val(($txt.val() || '') + "\n\n" + html);
          setState('Contenu inséré (zone texte).');
        }
      }

      alert('Description Amazon importée et ajoutée à la description longue.');

    } catch(e){
      setState('Erreur AJAX : ' + (e?.responseText || e?.message || e), true);
    } finally {
      $btn.prop('disabled', false).text('Importer la description Amazon (ASIN depuis SKU)');
    }
  });
});
