$(document).ready(function(){
	$('.view_full_object').click(function(e){
		var id = $(this).parents('tr').attr('id');
		var options = {
			'url' : '/twitter/tweet/~/' + id
		}
		$.ajax(options).done(function(data){
			var div = $('<div></div>').html('<pre>' + data + '</pre>').attr('style', 'position:absolute; height:300px; overflow-y:scroll; width:98%; margin:10px 1%;').click(function(){
				$(this).remove();
			});
			$('.body').prepend(div);
			
		});
		e.preventDefault();
	});
})
