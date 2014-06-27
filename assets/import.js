var totalEntries;
var currentRow;
var sectionID;
var uniqueAction;
var uniqueField;
var importURL;
var startTime;
var fieldIDs;


jQuery(function($){
    // Window resize function (for adjusting the height of the console):
    $(window).resize(function(){
        $("div.console").height($(window).height() - 350);
    }).resize();
	$('input[name=import-step-3]').click(function(event){
		event.preventDefault();
		$(this).parents('form').submit();
	});
	$('input[name=import-step-3]').parents('form').submit(function(event){
		event.preventDefault();
		var a = [];
		$('.fields.small').each(function(i){
			a.push($(this).find('option:selected').val());
			
		});	
		var ids = a.join();
		var loader = $('<div class="loader"><p>Exporting</p></div>');
		var span = $('<span></span>');
		var container = $('<div class="container"></div>');		
		loader.append(span);
		container.append(loader);
		$('#wrapper').append(container);
		var span1 = $('<span class="percent"></span>');
		$('.loader p').append(span1);
		var fields = {
			section : $('input[name=section]').val(),
			row : 0,
			file : $('input[name=file]').val(),
			uniqueaction : $('select[name=unique-action] option:selected').val(),
			uniquefield : $('input[name=unique-field]:checked').val(),
			fieldids : ids,
			totalrows : $('input[name=count]').val()
		}
		//console.log(fields);
		importRows(fields);
	
	});    
});

/**
 * Import 10 rows at the time
 * @param nr
 */
function importRows(fields)
{    
	console.log(fields);
	var importURL = Symphony.Context.get('symphony')+ '/extension/importexport/import/';
    var request = jQuery.ajax({
        url: importURL,
        async: true,
        type: 'post',
        cache: false,
        data: fields,
        success: function(data, textStatus){            
			var all = data;
			if(data.progress == 'success'){		
				if(data.row){						
						var percent = parseInt(data.row) / parseInt(data.count) * 100;						
						$('.container').show();
						$('.loader span:not(.percent)').animate({    width: Math.round(percent)+'%'	});
						$('.percent').html(Math.round(percent)+'% Completed');												
					}
					if(data){						
						
						newfields = {
							section : all.section,					
							row : ++all.row,							
							file : all.file,
							uniqueaction : all.uniqueaction,
							progress : all.progress,
							fieldids : all.fieldids,
							uniquefield : all.uniquefield,
							count : all.count
						}
						console.log(newfields);
						importRows(newfields);
					}
					
					
			}else{
				var url = Symphony.Context.get('symphony')+ '/blueprints/sections/';
				window.location.replace(url);
			}
        }
    });
	

}


/**
 * Get a variable from the HTML code
 * @param name  The name of the variable
 */
function getVar(name)
{
    return jQuery("var." + name).text();
}

/**
 * Put a message in the console
 * @param str   The content
 */
function put(str, cls)
{
    c = cls == null ? '' : ' class="' + cls + '"';
    jQuery("div.console").append('<span'+c+'>' + str + '</span>');
}

function two(x) {return ((x>9)?"":"0")+x}

function time(ms) {
    var sec = Math.floor(ms/1000);
    var min = Math.floor(sec/60);
    sec = sec % 60;
    t = two(sec);

    var hr = Math.floor(min/60);
    min = min % 60;
    t = two(min) + ":" + t;
    return t;
}