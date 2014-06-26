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
		var fields = {
			section : $('input[name=section]').val(),
			row : 1,
			file : $('input[name=file]').val(),
			uniqueaction : $('select[name=unique-action] option:selected').val(),
			uniquefield : $('input[name=unique-field]:checked').val(),
			fieldids : ids
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
	var importURL = Symphony.Context.get('symphony')+ '/extension/importexport/import/';
    var request = jQuery.ajax({
        url: importURL,
        async: true,
        type: 'post',
        cache: false,
        data: fields,
        success: function(data, textStatus){
            console.log(data);
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