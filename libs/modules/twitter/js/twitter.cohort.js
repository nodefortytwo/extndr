$(document).ready(function(){
	
	$('#days .btn').click(function(){
		
		if($(this).attr('id') == 'go'){
			go();
			return;
		}
		
		if($(this).hasClass('btn-inverse')){
			$(this).removeClass('btn-inverse');
		}else{
			$(this).addClass('btn-inverse');
		}
	})
	
});

function go(){
	var selected = $('#days .btn-inverse');
	var len = selected.length;
	if(len == 0){
		return;
	}else if(len == 1){
		window.location.replace('?from=' + $(selected[0]).attr('id'));
	}else if(len > 1){
		window.location.replace('?from=' + $(selected[0]).attr('id') + '&to=' + $(selected[len-1]).attr('id'));	
	}
}
