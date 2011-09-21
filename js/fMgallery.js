var fMg;
function fMgallery() {
	this.target_url = '';
	this.url = function() {
		return this.target_url + ((this.target_url.indexOf('?') == -1) ? '?' : '&');
	};
	
	this.data = {
		'categories':	{},
		'categoriesId':	{},
		'albums':		{},
		'albumsId':		{},
		'pictures':		{},
		'picturesId':	{}
	};
	this.show_now = {
		'category':	-1,
		'album':	-1,
		'picture':	-1
	};
	this.s = this.show_now;
	this.US = {
		'picture_header_width' : 0
	};
	
	this.album = {};
	this.album.get = function(category_id,force) {
		fMg.s.category = fMg.data.categoriesId[category_id];
		if(fMg.data.albums[category_id] == undefined || force == true) {
			$.ajax({
				url:	fMg.url() + "type=fMgallery&func=albums_get",
				type:	"post",
				cache:	false,
				data:	{
					"category_id":	category_id
				},
				success: function(data) {
					fMjson.parseRun(data);
				}
			});
		} else {
			fMjson.func.albums_get_insert(fMg.data.albums[category_id]);
		}
	};
	this.album.start = function(album_id,force) {
		fMg.s.album = fMg.data.albumsId[album_id];
		if(fMg.data.pictures[album_id] == undefined || force == true) {
			$.ajax({
				url:	fMg.url() + "type=fMgallery&func=album_start",
				type:	"post",
				cache:	false,
				data:	{
					"album_id":	album_id
				},
				success: function(data) {
					fMjson.parseRun(data);
				}
			});
		} else {
			fMjson.func.album_start_insert(fMg.data.pictures[album_id]);
		}
	};
	
	this.album.start_quick = function(album_id) {
		$.ajax({
			url:	fMg.url() + "type=fMgallery&func=album_start_quick",
			type:	"post",
			cache:	false,
			data:	{
				"album_id":	album_id
			},
			success: function(data) {
				fMjson.parseRun(data);
			}
		});
	};
	
	this.picture = {};
	this.picture.go = function(picture,callback) {
		if(fMg.s.album != -1) {
			if(picture.id) {
				fMg.s.picture = fMg.data.picturesId[picture.id];
			} else if(picture.i) {
				fMg.s.picture = fMg.data.pictures[fMg.s.album.id][picture.i];
			} else if(picture.func) {
				switch(picture.func) {
					case 'first': {
						fMg.s.picture = fMg.data.pictures[fMg.s.album.id][0];
					}	break;
					case 'left': {
						if(fMg.s.picture.i > 0) {
							fMg.s.picture = fMg.data.pictures[fMg.s.album.id][fMg.s.picture.i - 1];
						} else {
							return;
						}
					}	break;
					case 'right': {
						if(fMg.s.picture.i < (fMg.data.pictures[fMg.s.album.id].length - 1)) {
							fMg.s.picture = fMg.data.pictures[fMg.s.album.id][parseInt(fMg.s.picture.i) + 1];
						} else {
							return;
						}
					}	break;
				}
			} else {
				return;
			}
			if(typeof(callback) == 'function') return callback.call();
			if(typeof(fMg.picture.go_callback) == 'function') return fMg.picture.go_callback.call();
		}
	};
}
fMg = new fMgallery();