$(function() {

	$('.croppie-control').livequery(function () {


		var $upload  = $('input[name$="[upload]"]', $(this));
		var $x1  = $('input[name$="[x1]"]', $(this));
		var $y1  = $('input[name$="[y1]"]', $(this));
		var $x2  = $('input[name$="[x2]"]', $(this));
		var $y2  = $('input[name$="[y2]"]', $(this));
		var $croppie = $('[data-croppie]', $(this));

		$upload.on('change', function () {
			if (this.files && this.files[0]) {
				var reader = new FileReader();

				reader.onload = function (e) {
					$croppie.croppie('bind', {
						url: e.target.result
					});
				};

				reader.readAsDataURL(this.files[0]);
			}
			else {
				console.error("Sorry - you're browser doesn't support the FileReader API");
			}
		});

		var options = $(this).data('options');
		$croppie.croppie(
			$.extend({
				viewport: {
					width: 250,
					height: 250
				},
				boundary: {
					width: 300,
					height: 300
				},
				enableZoom: true,
				mouseWheelZoom: false
			},
			options,
			{
				update: function(cropper)
				{
					$x1.val(cropper.points[0]);
					$y1.val(cropper.points[1]);
					$x2.val(cropper.points[2]);
					$y2.val(cropper.points[3]);
				}
			})
		);

		var url = $(this).data('image-url');
		var x1 = parseInt($x1.val());
		var y1 = parseInt($y1.val());
		var x2 = parseInt($x2.val());
		var y2 = parseInt($y2.val());

		var image = new Image();
		image.src = url;
		image.onload = function()
		{
			x1 = x1 || 0;
			y1 = y1 || 0;
			x2 = x2 || image.width;
			y2 = y2 || image.height;

			$croppie.croppie('bind',
			{
				url: url,
				points: [x1, y1, x2, y2]
			});
		}


	});



});