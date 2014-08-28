var $ = jQuery;

String.prototype.htmlspecialchars = function() {
  var str = this;
  str = str.replace(/&/g,"&amp;");
  str = str.replace(/"/g,"&quot;");
  str = str.replace(/'/g,"&#039;");
  str = str.replace(/</g,"&lt;");
  str = str.replace(/>/g,"&gt;");
  return str;
};

function WpFlickrEmbed() {
  var self = this;
  
  self.page = 1;
  self.user_id = null;
  self.query = null;
  self.sort_by = null;
  self.photoset_id = null;

  self.alignments = ['alignment_none', 'alignment_left', 'alignment_center', 'alignment_right'];
  self.photos = {};
  self.flickr_url = '';
  self.title_text = '';



  self.handleFlickrError = function(code, msg) {
    $('#ajax_error_msg').text('Flickr error: ' + code + ' (' + msg + ')').show();
    $('#loader').hide();
  };

  self.handleAjaxError = function(XHR, status, errorThrown) {
    $('#ajax_error_msg').text('AJAX error: ' + status + ' (' + errorThrown + ')').show();
    $('#loader').hide();
  };

  self.getFlickrData = function(params, successCallback) {
    $('#ajax_error_msg').hide();
    $('#loader').show();

    params.format = 'json';
    params.nojsoncallback = 1;  // don't want give us JSONP response

    // first let's sign the request
    $.ajax({
      async: true,
      timeout: 15000,
      url: sign_request_url + encodeURIComponent(JSON.stringify(params)),
      dataType: 'json',
      success: function(data) {
        // now we call flickr
        $.ajax({
          async: true,
          timeout: 10000,
          type: 'POST',
          url: data.url,
          data: data.params,
          dataType: 'json',
          success: function(data) {
            if ('undefined' !== typeof data.stat && 'ok' !== data.stat) {
              return self.handleFlickrError(data.code || '', data.message || 'Flickr API returned an unknown error');
            }

            successCallback.call(self, data);
          },
          error: self.handleAjaxError
        });
      },
      error: self.handleAjaxError
    });
  };


  self.slugifySizeLabel = function(sizeLabel) {
    return sizeLabel.toLowerCase().replace(' ', '_');
  };


  self.flickrGetPhotoSizes = function(photo_id) {
    var params = {};
    params.photo_id = photo_id;
    params.method = 'flickr.photos.getSizes';
    params.time = (new Date()).getTime();

    self.getFlickrData(params, self.callbackPhotoSizes);
  };


  /**
   * Build DIV containing Radio button for selecting a size.
   * @return {*}
   */
  self.buildSizeSelectorRadioButtonDiv = function(sizeObj) {
    var size = sizeObj.slug;

    // e.g. 'medium_600' -> 'medium'
    var sizeCategory = (size.split('_') || [size])[0];
    if ('large_square' == sizeObj.slug) sizeCategory = 'square';

    var newSizeRadioInputId = sizeObj.idPrefix + '_' + size;

    var newSizeRadio = $('<input type="radio" />');
    newSizeRadio.attr({
      id: newSizeRadioInputId,
      name: sizeObj.idPrefix + '_size',
      value: size
    });
    newSizeRadio.attr('data-sizeCategory', sizeCategory);
    newSizeRadio.attr('data-width', sizeObj.width || '');
    newSizeRadio.attr('data-height', sizeObj.height || '');

    if (sizeObj.imgSrc && '' != sizeObj.imgSrc) {
      newSizeRadio.attr('rel', sizeObj.imgSrc);
    } else {
      newSizeRadio.attr('disabled', 'disabled');
    }

    var newSizeLabel = $('<label />');
    newSizeLabel.attr('for', newSizeRadioInputId);
    newSizeLabel.text(sizeObj.label);

    var newSizeDiv = $('<div />');
    newSizeDiv.addClass(size);
    newSizeDiv.append(newSizeRadio).append('<span>&nbsp;</span>').append(newSizeLabel);

    return newSizeDiv;
  };


  self.callbackPhotoSizes = function(data) {
    if (! data) return self.error(data);
    if (! data.sizes) return self.error(data);
    var list = data.sizes.size;
    if (! list) return self.error(data);
    if (! list.length) return self.error(data);

    var jqDisplaySizeDiv = $('#select_size div.sizes').empty();
    var jqLightboxSizeDiv = $('#select_lightbox_size div.sizes').empty();

    var originalSizeIncluded = false;

    for (i=0; i<list.length; ++i) {
      originalSizeIncluded = ('Original' == list[i].label);

      jqDisplaySizeDiv.append(self.buildSizeSelectorRadioButtonDiv({
        idPrefix: 'display',
        slug: self.slugifySizeLabel(list[i].label),
        imgSrc: list[i].source,
        width: list[i].width,
        height: list[i].height,
        label: list[i].label + ' (' + list[i].width + ' x ' + list[i].height + ')'
      }));

      jqLightboxSizeDiv.append(self.buildSizeSelectorRadioButtonDiv({
        idPrefix: 'lightbox',
        slug: self.slugifySizeLabel(list[i].label),
        imgSrc: list[i].source,
        width: list[i].width,
        height: list[i].height,
        label: list[i].label + ' (' + list[i].width + ' x ' + list[i].height + ')'
      }));
    }

    // original size disabled?
    if (!originalSizeIncluded) {
      jqDisplaySizeDiv.append(self.buildSizeSelectorRadioButtonDiv({
        idPrefix: 'display',
        slug: 'original',
        label: 'Original (not permitted)'
      }));

      jqLightboxSizeDiv.append(self.buildSizeSelectorRadioButtonDiv({
        idPrefix: 'lightbox',
        slug: 'original',
        label: 'Original (not permitted)'
      }));
    }

    jqDisplaySizeDiv.find(':radio').first().click();
    jqLightboxSizeDiv.find(':radio').first().click();

    $('#loader').hide();
    $('#put_dialog').show();
    $('#put_background').show();
  };

  self.changeSearchType = function() {
    if($('#flickr_search_0:checked').size()) {
      $('#flickr_search_query').show();
      $('#photoset').hide();
    }else if($('#flickr_search_1:checked').size()) {
      $('#flickr_search_query').hide();
      wpFlickrEmbed.flickrGetPhotoSetsList(function() {
        $('#photoset').show();
      });
    }else{
      $('#flickr_search_query').show();
      $('#photoset').hide();
    }
  };

  self.changeSortOrder = function() {
    wpFlickrEmbed.searchPhoto(0);
  };

  self.flickrGetPhotoSetsList = function(cb) {
    var params = {};
    params.user_id = flickr_user_id;
    params.format = 'json';
    params.method = 'flickr.photosets.getList';

    self.getFlickrData(params, function(data) {
      self.callbackPhotoSetsList(data);
      cb();
      $('#loader').hide();
    });
  };


  self.callbackPhotoSetsList = function(data) {
    if(!data || !data.photosets || !data.photosets.photoset) {
      $('#photoset').css('display', 'none');
    }
    for(var i=0;i<data.photosets.photoset.length;i++) {
      $('#photoset').append(new Option(data.photosets.photoset[i].title._content, data.photosets.photoset[i].id));
    }
  };

  self.searchPhoto = function(paging) {
    if(paging == 0) {
      self.page = 1;
      if($('#flickr_search_0:checked').size()) {
        self.user_id = flickr_user_id;
        self.photoset_id = null;
      }else if($('#flickr_search_1:checked').size()) {
        self.user_id = flickr_user_id;
        self.photoset_id = $('#photoset option:selected').val();
      }else{
        self.user_id = null;
        self.photoset_id = null;
      }
      self.query = $('#flickr_search_query').val();
      self.sort_by = $('#sort_by').val();
    }else{
      self.page += paging;
    }


    if(self.user_id) {
      self.flickrPhotoSearch({ text: self.query, page: self.page, user_id: self.user_id, photoset_id: self.photoset_id });
    }else{
      self.flickrPhotoSearch({ text: self.query, page: self.page });
    }
    return false;
  };



  self.flickrPhotoSearch = function(params) {
    params.per_page = 18;
    params.sort     = self.sort_by;
    params.extras = 'url_sq,url_m';

    if(!params.text && !params.user_id) {
      params.method   = 'flickr.photos.getRecent';
    }else if(params.photoset_id){
      params.method   = 'flickr.photosets.getPhotos';
    }else{
      params.method   = 'flickr.photos.search';
    }

    self.clearItems();

    self.getFlickrData(params, self.callbackSearchPhotos);
  };


  self.callbackSearchPhotos = function(data) {
    if(!data) return self.error(data);
    if(!data.photos && !data.photoset) return self.error(data);
    var photos = data.photos;
    if(!photos) photos = data.photoset;
    var list = photos.photo;
    if(!list) return self.error(data);
    if(!list.length) return self.error(data);

    self.clearItems();

    $('#pages').html(msg_pages.replace(/%1\$s/, photos.page).replace(/%2\$s/, photos.pages).replace(/%3\$s/, photos.total));

    if(photos.page > 1) {
      $('#prev_page').show();
    }
    if(photos.page < photos.pages) {
      $('#next_page').show();
    }

    self.photos = {};

    for(var i=0; i<list.length; i++) {
      var photo = list[i];

      photo.short_title = photo.title.replace(/^(.{17}).*$/, '$1...');

      var image_s_url = self.convertHTTPStoHTTP(photo.url_sq);

      var owner = photo.owner;
      if(!owner) {
        owner = self.user_id;
      }

      var flickr_url = null;

      if(1 == setting_photo_link) {
        flickr_url = 'http://farm'+photo.farm+'.static.flickr.com/'+photo.server+'/'+photo.id+'_'+photo.secret+'.jpg';
      } else if (0 == setting_photo_link) {
        flickr_url = 'http://www.flickr.com/photos/'+owner+'/'+photo.id+'/';
      }

      self.photos[photo.id] = new Object();
      self.photos[photo.id].title = photo.title;
      self.photos[photo.id].flickr_url = flickr_url;        

      var div = document.createElement('div');
      $(div).addClass('flickr_photo');

      var img = document.createElement('img');
      img.setAttribute('src', image_s_url);
      img.setAttribute('alt', photo.title);
      img.setAttribute('title', photo.title);
      img.setAttribute('rel', photo.id);
      $(img).addClass('flickr_image');
      $(img).click(function() {
        window['wpFlickrEmbed'].showInsertImageDialog($(this).attr('rel'));
      });

      var atag = document.createElement('a');
      atag.href = flickr_url;
      atag.title = atag.tip = "show on Flickr";
      atag.target = '_blank';
      atag.innerHTML = '<img src="'+plugin_img_uri+'/show-flickr.gif" alt="show on Flickr"/>';

      var title = document.createElement('div');
      $(title).addClass('flickr_title');

      var span = document.createElement('span');
      span.innerHTML = photo.short_title.replace(/(.{3})/g, '$1&wbr;').htmlspecialchars().replace(/&amp;wbr;/g, '<wbr/>');
      span.setAttribute('title', photo.title);
      span.setAttribute('rel', photo.id);
      $(span).click(function() {
        window['wpFlickrEmbed'].showInsertImageDialog($(this).attr('rel'));
      });

      title.appendChild(atag);
      title.innerHTML += '&nbsp;';
      title.appendChild(span);

      div.appendChild(img);
      div.appendChild(title);

      $('#items').append(div);
      $('#loader').hide();
    }
  };




  self.showInsertImageDialog = function(photo_id) {
    self.flickr_url = self.photos[photo_id].flickr_url;
    self.title_text = self.photos[photo_id].title;

    self.flickrGetPhotoSizes(photo_id);

    if(!$('#select_alignment :radio:checked').size()) {
      $('#alignment_none').attr('checked', 'checked');
    }

    $('#photo_title').val(self.title_text);
  };


  /**
   * Convert HTTPS URL into HTTP.
   * @param url a URL string
   * @return String
   */
  self.convertHTTPStoHTTP = function(url) {
    return url.replace('https:', 'http:');
  };


  self.insertImage = function() {
    var original_flickr_url = self.flickr_url,
      flickr_url = original_flickr_url;

    var title_text = $.trim($('#photo_title').val());
    if ('' == title_text) {
      alert('Please enter a title for the photo');
      return;
    }

    var img_url, img_width, img_height = null;
    if(0 < $('#select_size :radio:checked').size()) {
      var selectedSize = $('#select_size :radio:checked');
      img_url = self.convertHTTPStoHTTP(selectedSize.attr('rel'));
      img_width = selectedSize.attr('data-width');
      img_height = selectedSize.attr('data-height');
    }

    if(0 < $('#select_lightbox_size :radio:checked').size()) {
      flickr_url = self.convertHTTPStoHTTP($('#select_lightbox_size :radio:checked').attr('rel'));
    }

    var img = $('<img />');
    img.attr('src', img_url)
        .attr('width', img_width)
        .attr('height', img_height)
        .attr('alt', title_text)
        .attr('title', title_text);

    var a = $('<a />');
    a.attr('href', (flickr_url ? flickr_url : '#')).attr('title', title_text).attr('rel', setting_link_rel);
    a.addClass(setting_link_class);

    var p = $('<p />');

    var alignment = null;
    if($('#alignments :radio:checked').size()) {
      alignment = $('#alignments :radio:checked').val();
    }

    if(alignment != 'none') {
      if(alignment != 'center') {
        img.css('float', alignment).addClass('align'+alignment);
      }else{
        img.addClass('aligncenter');
        p.css('text-align', 'center');
      }
    }

    a.append(img);
    p.append(a);

    $('#put_dialog').hide();
    $('#put_background').hide();

    self.send_to_editor(p.html(), $('#continue_insert:checked').size() == 0);
  };



  self.cancelInsertImage = function() {
    $('#put_dialog').hide();
    $('#put_background').hide();
  };

  self.changeSize = function(e) {
    var elem = $(e.target);

    var sizeCategory = elem.attr('data-sizeCategory');
    if (!sizeCategory) return;

    var preview_img = elem.closest('.selector').find('.size_preview img');
    if (0 >= preview_img.size()) return;

    if(preview_img.attr('rel') != sizeCategory) {
      preview_img.attr('rel', sizeCategory);
      preview_img.attr('src', plugin_img_uri+'/size_'+sizeCategory+'.png');
    }
  };

  self.changeAlignment = function() {
    var alignment = null;
    if($('#alignments :radio:checked').size()) {
      alignment = $('#alignments :radio:checked').val();
    }
    if(alignment && $('#alignment_image').attr('rel') != alignment) {
      $('#alignment_preview').html('<img id="alignment_image" rel="'+alignment+'" src="'+plugin_img_uri+'/alignment_'+alignment+'.png" alt=""/>');
    }
  };



  self.clearItems = function() {
    $('#items').empty();
    $('#next_page').hide();
    $('#prev_page').hide();
  };



  self.error = function(data) {
    self.clearItems();

    if(data && data.photos && data.photos.photo) {
      $('#items').html(flickr_errors[0]);
    }else if(data && data.photoset && data.photoset.photo) {
      $('#items').html(flickr_errors[0]);
    }else{
      var code = data.code;
      if(!flickr_errors[code]) {
        code = 999;
      }
      self.handleFlickrError(code, flickr_errors[code]);
    }

    $("#loader").hide();
  };



  self.send_to_editor = function(h, close) {
    var ed;

    if ( typeof top.tinyMCE != 'undefined' && ( ed = top.tinyMCE.activeEditor ) && !ed.isHidden() ) {
      // restore caret position on IE
      if ( top.tinymce.isIE && ed.windowManager.insertimagebookmark )
        ed.selection.moveToBookmark(ed.windowManager.insertimagebookmark);

      if ( h.indexOf('[caption') === 0 ) {
        if ( ed.plugins.wpeditimage )
          h = ed.plugins.wpeditimage._do_shcode(h);
      } else if ( h.indexOf('[gallery') === 0 ) {
        if ( ed.plugins.wpgallery )
          h = ed.plugins.wpgallery._do_gallery(h);
      } else if ( h.indexOf('[embed') === 0 ) {
        if ( ed.plugins.wordpress )
          h = ed.plugins.wordpress._setEmbed(h);
      }

      ed.execCommand('mceInsertContent', false, h);
      $('iframe#tinymce:first').contents().find('img').each(function() { self.src = self.src });

    } else if ( typeof top.edInsertContent == 'function' ) {
      top.edInsertContent(top.edCanvas, h);
    } else {
      top.jQuery( top.edCanvas ).val( top.jQuery( top.edCanvas ).val() + h );
    }

    if(close) {
      top.tb_remove();
    }
  };
}

var wpFlickrEmbed = new WpFlickrEmbed();

$(document).ready(function() {

  $('div#alignments').on('change', ':radio', wpFlickrEmbed.changeAlignment);
  $('.sizes').on('change', ':radio', wpFlickrEmbed.changeSize);
  $('input.searchTypes').on('change', wpFlickrEmbed.changeSearchType);
  $('select#sort_by').on('change', wpFlickrEmbed.changeSortOrder);

  new Image().src = plugin_img_uri+'/alignment_none.png';
  new Image().src = plugin_img_uri+'/alignment_left.png';
  new Image().src = plugin_img_uri+'/alignment_center.png';
  new Image().src = plugin_img_uri+'/alignment_right.png';

  new Image().src = plugin_img_uri+'/size_square.png';
  new Image().src = plugin_img_uri+'/size_thumbnail.png';
  new Image().src = plugin_img_uri+'/size_small.png';
  new Image().src = plugin_img_uri+'/size_medium.png';
  new Image().src = plugin_img_uri+'/size_large.png';
  new Image().src = plugin_img_uri+'/size_original.png';

  wpFlickrEmbed.searchPhoto(0);
});
