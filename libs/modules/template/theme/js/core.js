$(document).ready(function(){
	$(":file").filestyle({input:false});
	$('.table-sortable').tablesorter({
		
	});
	
	updateDates();
		
})

function updateDates(){
	$('.date').each(function(){
		
		var date = moment.unix($(this).attr('data-timestamp'));
		
		$(this).html(date.fromNow());
		
	}).promise().done(function(){
		//update the relative dates every 5 seconds.
		setTimeout("updateDates()", 5000);
	});
}
