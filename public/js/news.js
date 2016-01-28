$f.news = {
	openNews:function(e){
		$(".news dt").next('dd').not(e.next('dd')).slideUp('slow');
		e.next('dd').slideToggle();
	}
};