$(document).ready(function(){
	var options = {
		url : '/twitter/ratelimit/~/search/-search-tweets/json/',
		dataType : 'json'
	};
	$.ajax(options).done(function(data){
		$("#info").html(data.html);
	})
})
