$(function () {

	// CropImageControl
	$('.cropImageControl').livequery(function() {
		var $_this = $(this);

		// Crop
		var $image = $_this.find('.cropImageImage');
		var $imageWrapper = $image.parent();
        var $removeButton = $_this.find('.cropImageRemove');

		var aspectRatio = $_this.attr('data-aspect-ratio');
        var placeWidth = $_this.attr('data-place-width');
		var placeHeight = $_this.attr('data-place-height');


		var $filename = $_this.find('.input-filename');
		var $sess_key= $_this.find('.input-sess_key');
		var $x = $_this.find('.input-x');
		var $y = $_this.find('.input-y');
		var $w = $_this.find('.input-w');
		var $h = $_this.find('.input-h');

		var setImage = function(imgMiniSize, imgOrigSize) {
			// ziskame rozmery Zmenseniny


            // Nova miniatura
            if(imgMiniSize) {
                if(imgMiniSize.src) {
                    $image = $('<img />').attr('src', imgMiniSize.src);
                } // jinak je image ten co sme ho vybrali na zacatko

                $imageWrapper.css({
                    'margin-left': -(imgMiniSize.width / 2),
                    'margin-top':  -(imgMiniSize.height / 2)
                });

                $imageWrapper.html( $image );
            }

            var sx = parseInt($x.val()) > 0 ? parseInt($x.val()) : 0;
            var sy = parseInt($y.val()) > 0 ? parseInt($y.val()) : 0;
            var sw = parseInt($w.val()) > 0 ? parseInt($w.val()) : imgOrigSize.width;
            var sh = parseInt($h.val()) > 0 ? parseInt($h.val()) : imgOrigSize.height;

            $image.Jcrop({
                trueSize: [imgOrigSize.width, imgOrigSize.height],
                setSelect: [sx, sy, sx+sw, sy+sh], // x y x2 y2
                onSelect: function(coords) {
                    refreshInputs(coords.w, coords.h, coords.x, coords.y);
                },
                onChange: function(coords) {
                    refreshInputs(coords.w, coords.h, coords.x, coords.y);
                },
                bgOpacity: 0.5,
                bgColor: 'rgba(255,255,255,0.5)',
                aspectRatio: aspectRatio
            });

		}
		var refreshInputs = function(w,h,x,y) { $x.val(Math.round(x)); $y.val(Math.round(y)); $w.val(Math.round(w)); $h.val(Math.round(h)); }


        // Prvni nacteni
		if($filename.val())
			setImage( null, {width: $_this.attr('data-image-orig-width'), height: $_this.attr('data-image-orig-height')} );

		// Tlačítko upload
		var uploader = new qq.FileUploader({
			element: $_this.find('.cropImageUpload').get(0),
			multiple: false,
			template:
				'<div class="qq-uploader">' +
					'<div class="qq-upload-drop-area"><span>Drop files here to upload</span></div>' +
					'<div class="qq-upload-button"><span class="btn btn-xs btn-primary">Nahrát obrázek</span></div>' +
					'<ul class="hide qq-upload-list"></ul>' +
					'</div>',
			action: $_this.find('.cropImageUpload').attr('data-upload-script'),

            onSubmit: function(id, fileName) {
//                var $icon = $('<i></i>').css({ height: thumbHeight, lineHeight: thumbHeight+'px', width: thumbWidth }).addClass('icon-spinner icon-spin bigger-300');
//                var $spinner = $('<div></div>').addClass('spinner').append($icon).append('<span class="load">0%</span>');

//                var $li = $('<li></li>').width(thumbWidth).height(thumbHeight).attr('data-upload-id', id).html($spinner);
//                $fotos.append( $li );
            },
            onProgress: function(id, fileName, loaded, total){
//                var percent = Math.round((loaded/total) * 100, 2);
//                $fotos.find('li[data-upload-id='+id+'] .load').text(percent+'%');
            },
			onComplete: function(id, filename, responseJSON){
				$filename.val(responseJSON.filename);
				$sess_key.val(responseJSON.sess_key);
				$x.val(0);
				$y.val(0);
				$w.val(0);
				$h.val(0);
				setImage(responseJSON.imgMiniSize, responseJSON.imgOrigSize);
			},
			debug: false
		});

        $removeButton.click(function(e){
            e.preventDefault();

            if(!confirm('Opravdu chcete tento obrázek vymazat?'))
                return;

            $filename.val('');
            $sess_key.val('');
            $x.val('');
            $y.val('');
            $w.val('');
            $h.val('');
            $imageWrapper.html('');
        });

	});
	
});


//function getImageLink(params) {
//	return '/image/?' + http_build_query(params);
//}


function getParam( name, url )
{
	name = name.replace(/[\[]/,"\\\[").replace(/[\]]/,"\\\]");
	var regexS = "[\\?&]"+name+"=([^&#]*)";
	var regex = new RegExp( regexS );
	var results = regex.exec( url );
	if( results == null )
		return "";
	else
		return decodeURIComponent(results[1]);
}
function http_build_query (formdata, numeric_prefix, arg_separator) {
	var value, key, tmp = [], that = this;
	var _http_build_query_helper = function (key, val, arg_separator)
	{
		var k, tmp = [];
		if (val === true) {
			val = "1";
		} else if (val === false) {
			val = "0";
		}

		if (val !== null && typeof(val) === "object") {
			for (k in val) {
				if (val[k] !== null) {
					tmp.push(_http_build_query_helper(key + "[" + k + "]", val[k], arg_separator));
				}
			}
			return tmp.join(arg_separator);
		} else if (typeof(val) !== "function") {
			return encodeURIComponent(key) + "=" + encodeURIComponent(val);
		} else {
			throw new Error('There was an error processing for http_build_query().');
		}
	};

	if (!arg_separator) {
		arg_separator = "&";
	}
	for (key in formdata) {
		value = formdata[key];
		if (numeric_prefix && !isNaN(key)) {
			key = String(numeric_prefix) + key;
		}
		tmp.push(_http_build_query_helper(key, value, arg_separator));
	}
	return tmp.join(arg_separator);
}




