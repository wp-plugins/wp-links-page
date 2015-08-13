(function($) {

jQuery(document).ready(function($) {
$(window).load(function() {

	
	$('.update').hide();
	$('.message').hide();
	$('.update-message').hide();
	$('.message-limit').hide();

	 $("form#add-link").submit(function(e){
	    e.preventDefault();
		var count = $('.wp-list-table tr').length;
		if (count >= 11) {
			$('.message-limit').show();
		} else {
		 $('button#saveimg').html('Saving...Please Wait.');
		var url = $('#url-input').val();
		if (url.indexOf("www") < 0 ) {
			if (url.indexOf("http") < 0 ) {
				url = "http://www."+url;
			}
		}
		if (url.indexOf("http") < 0 ) {
			url = "http://"+url;
		}
		var desc = $('#description-input').val();
		var weight = parseInt($('#sort tr:last td.index').html(), 10)+1;
	 	$.ajax({
        	url: 'https://www.googleapis.com/pagespeedonline/v1/runPagespeed?url=' + url + '&screenshot=true',
        	type: 'GET',
        	dataType: 'json',
			success: function(data) {
				
           		data = data.screenshot.data.replace(/_/g, '/').replace(/-/g, '+');
            	$(this).attr('src', 'data:image/jpeg;base64,' + data);	
				var base64img = data;
				$.post(ajax_object.ajax_url,{
						"action": "wplpf_ajax",
						"data": base64img,
						"url": url,
						"weight": weight,
						"description": desc
					},function(response){
						  location.reload(true);
						  window.location = self.location;
						  $('button#saveimg').prop("disabled",false);
    				}
		);
            },
			error: function() {
						alert('There was an error getting the screenshot. Please make sure the url is valid and try again.');
						location.reload(true);
						window.location = self.location;
					},
			fail: function(data) {
				alert('Please enter a valid URL.');
			}
    	});
		$('button#saveimg').prop("disabled",true);
		}
	});
	
	$('.delete').click(function(e) {
		var fid = $(this).attr('id');
		var url = $('td.delete').attr('id');
		$.post(ajax_object.ajax_url,{
				"action": "wplpf_ajax_delete",
				"id":fid,
				"url":url,
			},function() {
				location.reload(true);
				window.location = self.location;
            }
    	);
		e.stopPropagation();
	});
	
	$('.edit').click(function() {
		var fid = $(this).attr('id');
		var url = $('#'+fid+' td.url').html();
		var desc = $('#'+fid+' td.description').html();
		$('#'+fid+' td.url').html('<label for="url" >Link Display</label><input id="url-input" type="text" name="url" value="'+url+'" />');
		$('#'+fid+' td.description').html('<label for="description" >Description</label><input id="description-input" type="text" name="description" value="'+desc+'" />');
		$('#'+fid+' .update').show();
		$('#'+fid+' button.edit').hide();
	});
	
	
	$('.update').click(function() {
		var fid = $(this).attr('id');
		var url = $('#'+fid+' td.url input').val();
		var desc = $('#'+fid+' td.description input').val();
		$.post(ajax_object.ajax_url,{
				"action": "wplpf_ajax_update",
				"id":fid,
				"url":url,
				"desc":desc,
			},function() {
				location.reload(true);
				window.location = self.location;
            }
    	);
	});
	
	$('#update-screenshots').click(function() {
		$('.update-message').show();
		$.post(ajax_object.ajax_url,{
				"action": "wplpf_ajax_update_screenshots",
			}, function() {
				$('#update-screenshots').prop("disabled",false);
				location.reload(true);
				window.location = self.location;
            }
		);
		$('#update-screenshots').prop("disabled",true);
		$('#update-screenshots').html("Please Wait...");
	});
	
	$('#save-weight').click(function() {
		var links_update = [];
		$('tbody .index').each(function() {
			id = $(this).attr('id');
			weight = $(this).html();
			link_update = { id: id, weight: weight, };
			links_update.push(link_update);
		});
		$.post(ajax_object.ajax_url,{
				'action': 'wplpf_ajax_weight',
				'links_update': links_update,
			}, function() {
			location.reload(true);
			window.location = self.location;
			});
	});
	
	var fixHelperModified = function(e, tr) {
    var $originals = tr.children();
    var $helper = tr.clone();
    $helper.children().each(function(index) {
        $(this).width($originals.eq(index).width())
    });
    return $helper;
	},
    updateIndex = function(e, ui) {
		$('.message').show();
        $('td.index', ui.item.parent()).each(function (i) {
            $(this).html(i + 1);
        });
    };

	$("#sort tbody").sortable({
		cursor: "move",
		helper: fixHelperModified,
		 start: function(event, ui) {
            ui.item.addClass('changed');
        },
		stop: updateIndex
	})

});
});
})(jQuery);