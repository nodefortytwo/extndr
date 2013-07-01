$(document).ready(function(){
	
	update_table();

});

function update_table(){
	var url = '~/json/';
	console.log('calling ' + url);
	$.ajax({
		url : url,
		dataType : 'json',
		success : function(data) {
			$('#streams').html(data.html);
		}
	});
	t=setTimeout("update_table()",5000);
}
