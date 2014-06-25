jQuery(function($){
    $("ul.importer-nav a").click(function(){
        $("ul.importer-nav a").removeClass("active");
        $(this).addClass("active");
        $("div.importer").hide();
        $("div."+$(this).attr("rel")).show();
        return false;
    });

    if(window.location.hash.replace('#', '') == 'multi')
    {
        $("ul.importer-nav a[rel=multilanguage]").click();
    }
	$('input[name=export]').click(function(e){
			e.preventDefault();			
			var loader = $('<div class="loader"><p>Exporting</p></div>');
			var span = $('<span></span>');
			var container = $('<div class="container"></div>');
			
			loader.append(span);
			container.append(loader);
			$('#wrapper').append(container);
			var span1 = $('<span class="percent"></span>');
			$('.loader p').append(span1);
			xportcsv();			
			$('input[name=export]').parents('form').submit(function(){
				event.preventDefault();
			});
	});
	
});
function callXport(fields){
	var new_fields = fields;
	xportcsv(new_fields);
	$('input[name=export]').parents('form').submit(function(){
				event.preventDefault();
	});
}
function xportcsv(fields){
		var newfields = fields;				
		if(newfields == null){
			newfields = {section : $('select[name=section-export] option:selected').val(),page : 1,limit:500,type:$('select[name=export-type] option:selected').val()};
		}						
		
		importURL = Symphony.Context.get('symphony')+ '/extension/importexport/export/';		
		
		var request = jQuery.ajax({
			url: importURL,
			async: true,
			type: 'post',
			cache: false,
			data: newfields,
			success: function(data, textStatus){
				
				
				if(data['progress'] == 'success'){					
					if(data['page']){						
						var percent = parseInt(data['page']) / parseInt(data['total-pages']) * 100;						
						$('.container').show();
						$('.loader span:not(.percent)').animate({    width: Math.round(percent)+'%'	});
						$('.percent').html(Math.round(percent)+'% Completed');												
					}
					if(data){						
						all = data;
						newfields = {
							section : all['section'],					
							page : ++all['page'],
							limit : all['limit'],
							type: all['type']
						}
					}					
					callXport(newfields);
				}else if(data['progress'] == 'headers'){					
					var file = data['file'];
					var type = data['type'];
					console.log(data);
					var url = Symphony.Context.get('symphony')+ '/extension/importexport/download/?file='+file+'&type='+type;
					window.location.replace(url)
					
				}else{					
					all = data;					
					$('.container').remove();
					if(all['type'] != 'json'){
						headers = {
							headers: $('select[name=section-export] option:selected').val(),
							type : $('select[name=export-type] option:selected').val()
						}
						callXport(headers);					
					}
				}
				
			}
		});		
		request.fail(function(j,m){
			console.log(m);
			console.log(j.responseText);
		
		});	
}
