jQuery(function($){
	registerExport();
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
			var newfields = {section : $('select[name=section-export] option:selected').val(),page : 1,limit:500,type:$('select[name=export-type] option:selected').val()};			
			xportcsv(newfields);			
			$('input[name=export]').parents('form').submit(function(){
				event.preventDefault();
			});
	});
	
});

function QueryStringToJSON() {            
    var pairs = location.search.slice(1).split('&');
    
    var result = {};
    pairs.forEach(function(pair) {
        pair = pair.split('=');
        result[pair[0]] = decodeURIComponent(pair[1] || '');
    });
    return JSON.stringify(result);
}


function registerExport(){
	
	
	$('.export-button').click(function(event){
		event.preventDefault();
		
		var loader = $('<div class="loader"><p>(Exporting) </p></div>');
			var span = $('<span></span>');
			var container = $('<div class="container"></div>');					
			loader.append(span);
			container.append(loader);
			$('#wrapper').append(container);
			var span1 = $('<span class="percent"></span>');
			$('.loader p').append(span1);
			var queryString = window.location.search;
			if(queryString.length != ''){				
				var query_string = QueryStringToJSON();
				var newfields = {filter : query_string,section : $('.export-button').attr('data-sectionhandle'),page : 1,limit:500,type:$('.export-entries option:selected').val()};			
			}else{
				var newfields = {section : $('.export-button').attr('data-sectionhandle'),page : 1,limit:500,type:$('.export-entries option:selected').val()};			
			}
			//console.log(newfields);
			xportcsv(newfields);
	});
}
function callXport(fields){	
	var new_fields = fields;
	xportcsv(new_fields);
	$('input[name=export]').parents('form').submit(function(){
				event.preventDefault();
	});
}

function xportcsv(fields){
		var newfields = fields;						
		var query_string = QueryStringToJSON();
		importURL = Symphony.Context.get('symphony')+ '/extension/importexport/export/';		
		console.log(newfields);
		var request = jQuery.ajax({
			url: importURL,
			async: true,
			type: 'post',
			cache: false,
			data: newfields,
			success: function(data, textStatus){
				all = data;

				if(data['progress'] == 'success'){					
					if(data['page']){						
						var percent = parseInt(data['page']) / parseInt(data['total-pages']) * 100;						
						$('.container').show();
						$('.loader span:not(.percent)').animate({    width: Math.round(percent)+'%'	});
						$('.percent').html(Math.round(percent)+'% Completed');												
					}
					if(data){						
						if(query_string != ''){
							newfields = {
								section : all['section'],					
								page : ++all['page'],
								limit : all['limit'],
								type: all['type'],
								filter : query_string
							}
						}else{
							newfields = {
								section : all['section'],					
								page : ++all['page'],
								limit : all['limit'],
								type: all['type']
							}
						}
					}					
					callXport(newfields);
				}else if(data['progress'] == 'headers'){					
					var file = data['file'];
					var type = data['type'];
					alert(data['msg']);
					var url = Symphony.Context.get('symphony')+ '/extension/importexport/download/?file='+file+'&type='+type;
					window.location.replace(url)
				}else if(data['progress'] == 'noentries'){
					alert('There are no Entries in this section');
								
				}else{					
					all = data;					
					$('.container').remove();
							headers = {
								headers: all.section,
								type : all.type
							}
							callXport(headers);	
				}
				
			}
		});		
		request.fail(function(j,m){
			console.log(m);
			console.log(j.responseText);
		
		});	
}
