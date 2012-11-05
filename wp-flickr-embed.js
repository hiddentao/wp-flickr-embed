var $ = jQuery;

String.prototype.htmlspecialchars = function() {
  var str = this;
  str = str.replace(/&/g,"&amp;");
  str = str.replace(/"/g,"&quot;");
  str = str.replace(/'/g,"&#039;");
  str = str.replace(/</g,"&lt;");
  str = str.replace(/>/g,"&gt;");
  return str;
}

String.prototype.unhtmlspecialchars = function() {
  var str = this;
  str = str.replace(/&amp;/g, "&");
  str = str.replace(/&quot;/g, "\"");
  str = str.replace(/&#039;/g, "'");
  str = str.replace(/&lt;/g, "<");
  str = str.replace(/&gt;/g, ">");
  return str;
}

function WpFlickrEmbed() {
  this.page = 1;
  this.user_id = null;
  this.query = null;
  this.photoset_id = null;

  this.alignments = ['alignment_none', 'alignment_left', 'alignment_center', 'alignment_right'];
  this.sizes = new Object();
  this.sizes.Square = 'size_sq';
  this.sizes.Thumbnail = 'size_t';
  this.sizes.Small = 'size_s';
  this.sizes.Medium = 'size_m';
  this.sizes.Large = 'size_l';
  this.sizes.Original = 'size_o';

  this.photos = {};
  this.flickr_url = '';
  this.title_text = '';

  this.flickrGetPhotoSizes = function(photo_id) {
    var params = {};
    params.api_key = flickr_api_key;
    params.photo_id = photo_id;
    params.format = 'json';
    params.jsoncallback = 'wpFlickrEmbed.callbackPhotoSizes';
    params.method = 'flickr.photos.getSizes';

    var url = '//www.flickr.com/services/rest/?'+
        this.obj2query(params) + '&time='+(new Date()).getTime();

    $.getScript(url);
  }

  this.callbackPhotoSizes = function(data) {
    if (! data) return this.error(data);
    if (! data.sizes) return this.error(data);
    var list = data.sizes.size;
    if (! list) return this.error(data);
    if (! list.length) return this.error(data);

    for(label in this.sizes) {
      var size = this.sizes[label];
      var i = 0;
      for(i=0;i<list.length;i++) {
        if(list[i].label == label && list[i].source != '') {
          $('.div_'+size).show();
          $('.'+size).attr('rel', list[i].source);
          if($('.div_'+size+'_disabled').size()) {
            $('.div_'+size+'_disabled').hide();
          }
          break;
        }
      }
      if(i >= list.length) {
        $('.div_'+size).hide();
        $('.'+size).removeAttr('rel');
        if($('.div_'+size+'_disabled').size()) {
          $('.div_'+size+'_disabled').show();
        }
      }
    }
    $('#put_dialog').show();
    $('#put_background').show();
  }

  this.changeSearchType = function() {
    if($('#flickr_search_0:checked').size()) {
      $('#flickr_search_query').show();
      $('#photoset').hide();
    }else if($('#flickr_search_1:checked').size()) {
      $('#flickr_search_query').hide();
      $('#photoset').show();
    }else{
      $('#flickr_search_query').show();
      $('#photoset').hide();
    }
  }

  this.flickrGetPhotoSetsList = function() {
    var params = {};
    params.api_key = flickr_api_key;
    params.user_id = flickr_user_id;
    params.format = 'json';
    params.jsoncallback = 'wpFlickrEmbed.callbackPhotoSetsList';
    params.method = 'flickr.photosets.getList';

    var url = '//www.flickr.com/services/rest/?'+
        this.obj2query(params) + '&time='+(new Date()).getTime();

    $.getScript(url);
  }

  this.callbackPhotoSetsList = function(data) {
    if(!data || !data.photosets || !data.photosets.photoset) {
      document.getElemenetById('photosets').style.display = 'none';
    }
    var index = 0;
    for(var i=0;i<data.photosets.photoset.length;i++) {
      $('#photoset').append(new Option(data.photosets.photoset[i].title._content, data.photosets.photoset[i].id));
    }
  }

  this.searchPhoto = function(paging) {
    if(paging == 0) {
      this.page = 1;
      if($('#flickr_search_0:checked').size()) {
        this.user_id = flickr_user_id;
        this.photoset_id = null;
      }else if($('#flickr_search_1:checked').size()) {
        this.user_id = flickr_user_id;
        this.photoset_id = $('#photoset option:selected').val();
      }else{
        this.user_id = null;
        this.photoset_id = null;
      }
      this.query = $('#flickr_search_query').val();
    }else{
      this.page += paging;
    }
    if(this.user_id) {
      this.flickrPhotoSearch({ api_key: flickr_api_key, text: this.query, page: this.page, user_id: this.user_id, photoset_id: this.photoset_id });
    }else{
      this.flickrPhotoSearch({ api_key: flickr_api_key, text: this.query, page: this.page });
    }
    return false;
  }
  this.flickrPhotoSearch = function(params) {
    params.per_page = 18;
    params.sort     = 'date-posted-desc';
    params.format   = 'json';
    params.jsoncallback = 'wpFlickrEmbed.callbackSearchPhotos';
    params.extras = 'url_sq,url_m';

    if(!params.text && !params.user_id) {
      params.method   = 'flickr.photos.getRecent';
    }else if(params.photoset_id){
      params.method   = 'flickr.photosets.getPhotos';
    }else{
      params.method   = 'flickr.photos.search';
    }

    this.clearItems();
    $('#items').html('<img src="'+plugin_img_uri+'/loading.gif" />');

    var url = '//www.flickr.com/services/rest/?'+
        this.obj2query(params) + '&time='+(new Date()).getTime();

    console.log(url);

    $.getScript(url);
  }
  this.callbackSearchPhotos = function(data) {
    if(!data) return this.error(data);
    if(!data.photos && !data.photoset) return this.error(data);
    var photos = data.photos;
    if(!photos) photos = data.photoset;
    var list = photos.photo;
    if(!list) return this.error(data);
    if(!list.length) return this.error(data);

    this.clearItems();

    $('#pages').html(msg_pages.replace(/%1\$s/, photos.page).replace(/%2\$s/, photos.pages).replace(/%3\$s/, photos.total));

    if(photos.page > 1) {
      $('#prev_page').show();
    }
    if(photos.page < photos.pages) {
      $('#next_page').show();
    }

    this.photos = {};

    for(var i=0; i<list.length; i++) {
      var photo = list[i];

      photo.short_title = photo.title.replace(/^(.{17}).*$/, '$1...');
      photo.title = photo.title;

      var image_s_url = photo.url_sq;

      var image_m_url = photo.url_m;

      var owner = photo.owner;
      if(!owner) {
        owner = this.user_id;
      }

      var flickr_url = 'http://www.flickr.com/photos/'+
          owner+'/'+photo.id+'/';

      if(setting_photo_link) {
        flickr_url = 'http://farm'+photo.farm+'.static.flickr.com/'+photo.server+
            '/'+photo.id+'_'+photo.secret+'.jpg';
      }

      this.photos[photo.id] = new Object();
      this.photos[photo.id].title = photo.title;
      this.photos[photo.id].flickr_url = flickr_url;

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
    }
  }

  this.showInsertImageDialog = function(photo_id) {
    this.flickr_url = this.photos[photo_id].flickr_url;
    this.title_text = this.photos[photo_id].title;

    this.flickrGetPhotoSizes(photo_id);

    if(!$('#select_alignment :radio:checked').size()) {
      $('#alignment_none').attr('checked', 'checked');
    }

    if(!$('#select_size :radio:checked').size()) {
      $('#size_t').attr('checked', 'checked');
    }

    if(!$('#select_lightbox_size :radio:checked').size()) {
      $('#lightbox_size_t').attr('checked', 'checked');
    }
  }

  this.insertImage = function() {
    var flickr_url = this.flickr_url;
    var title_text = this.title_text;

    var img_url = null;
    if(0 < $('#select_size :radio:checked').size()) {
      img_url = $('#select_size :radio:checked').attr('rel');
    }

    if(0 < $('#select_lightbox_size :radio:checked').size()) {
      flickr_url = $('#select_lightbox_size :radio:checked').attr('rel');
    }

    var img = $('<img />');
    img.attr('src', img_url).attr('alt', title_text).attr('title', title_text);

    var a = $('<a />');
    a.attr('href', flickr_url).attr('title', title_text).attr('rel', setting_link_rel);
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

    this.send_to_editor(p.html(), $('#continue_insert:checked').size() == 0);
  }

  this.cancelInsertImage = function() {
    $('#put_dialog').hide();
    $('#put_background').hide();
  }

  this.changeSize = function(e) {
    var elem = $(e.target);

    var size = elem.val();
    if (!size) return;

    var preview_img = elem.closest('.selector').find('.size_preview img');
    if (0 >= preview_img.size()) return;

    if(preview_img.attr('rel') != size) {
      preview_img.attr('rel', size);
      preview_img.attr('src', plugin_img_uri+'/'+size+'.png');
    }
  };

  this.changeAlignment = function() {
    var alignment = null;
    if($('#alignments :radio:checked').size()) {
      alignment = $('#alignments :radio:checked').val();
    }
    if(alignment && $('#alignment_image').attr('rel') != alignment) {
      $('#alignment_preview').html('<img id="alignment_image" rel="'+alignment+'" src="'+plugin_img_uri+'/alignment_'+alignment+'.png" alt=""/>');
    }
  }

  this.clearItems = function() {
    $('#items').empty();
    $('#next_page').hide();
    $('#prev_page').hide();
  }

  this.obj2query = function(obj) {
    var list = [];
    for(var key in obj) {
      var k = encodeURIComponent(key);
      var v = encodeURIComponent(obj[key]);
      list[list.length] = k+'='+v;
    }
    var query = list.join('&');
    return query;
  }

  this.error = function(data) {
    this.clearItems();

    if(data && data.photos && data.photos.photo) {
      $('#items').html(flickr_errors[0]);
    }else if(data && data.photoset && data.photoset.photo) {
      $('#items').html(flickr_errors[0]);
    }else{
      var code = data.code;
      if(!flickr_errors[code]) {
        code = 999;
      }
      alert(data.code + ':' + flickr_errors[code]);
    }
  }

  this.send_to_editor = function(h, close) {
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
      $('iframe#tinymce:first').contents().find('img').each(function() { this.src = this.src });

    } else if ( typeof top.edInsertContent == 'function' ) {
      top.edInsertContent(top.edCanvas, h);
    } else {
      top.jQuery( top.edCanvas ).val( top.jQuery( top.edCanvas ).val() + h );
    }

    if(close) {
      top.tb_remove();
    }
  }
}

var wpFlickrEmbed = new WpFlickrEmbed();

$(document).ready(function() {

  $('div#alignments :radio').change(wpFlickrEmbed.changeAlignment);
  $('div.sizes :radio').change(wpFlickrEmbed.changeSize);
  $('input.searchTypes').change(wpFlickrEmbed.changeSearchType);

  new Image().src = plugin_img_uri+'/alignment_none.png';
  new Image().src = plugin_img_uri+'/alignment_left.png';
  new Image().src = plugin_img_uri+'/alignment_center.png';
  new Image().src = plugin_img_uri+'/alignment_right.png';

  new Image().src = plugin_img_uri+'/size_sq.png';
  new Image().src = plugin_img_uri+'/size_t.png';
  new Image().src = plugin_img_uri+'/size_s.png';
  new Image().src = plugin_img_uri+'/size_m.png';
  new Image().src = plugin_img_uri+'/size_l.png';
  new Image().src = plugin_img_uri+'/size_o.png';

  wpFlickrEmbed.searchPhoto(0);

  if(flickr_user_id) {
    wpFlickrEmbed.flickrGetPhotoSetsList();
  }
});
