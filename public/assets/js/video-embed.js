// public/assets/js/video-embed.js
// URL → embed iframe URL transformer for TikTok / YouTube / Facebook.
// No API calls; pure pattern matching on the public URL.

(function (global) {
  function detectPlatform(url) {
    if (!url) return null;
    var u = String(url).toLowerCase();
    if (u.indexOf('tiktok.com') >= 0)   return 'tiktok';
    if (u.indexOf('facebook.com') >= 0 || u.indexOf('fb.watch') >= 0) return 'facebook';
    if (u.indexOf('youtube.com') >= 0 || u.indexOf('youtu.be') >= 0)  return 'youtube';
    return null;
  }

  function buildEmbedUrl(url) {
    var platform = detectPlatform(url);
    if (!platform) return null;

    if (platform === 'tiktok') {
      var m = String(url).match(/\/video\/(\d+)/);
      if (!m) return null;
      return { platform: platform, originalUrl: url, embedUrl: 'https://www.tiktok.com/embed/v2/' + m[1] };
    }

    if (platform === 'facebook') {
      return {
        platform: platform, originalUrl: url,
        embedUrl: 'https://www.facebook.com/plugins/video.php?href=' + encodeURIComponent(url) + '&show_text=false',
      };
    }

    if (platform === 'youtube') {
      var id = null;
      var s = String(url);
      if (s.indexOf('youtu.be/') >= 0) {
        id = s.split('youtu.be/')[1].split(/[?&]/)[0] || null;
      } else if (s.indexOf('watch?v=') >= 0) {
        id = s.split('watch?v=')[1].split('&')[0] || null;
      } else if (s.indexOf('/shorts/') >= 0) {
        id = s.split('/shorts/')[1].split(/[?&]/)[0] || null;
      } else if (s.indexOf('/embed/') >= 0) {
        id = s.split('/embed/')[1].split(/[?&]/)[0] || null;
      }
      if (!id) return null;
      return { platform: platform, originalUrl: url, embedUrl: 'https://www.youtube.com/embed/' + id };
    }

    return null;
  }

  global.VideoEmbed = {
    detectPlatform: detectPlatform,
    buildEmbedUrl: buildEmbedUrl,
  };
})(window);
