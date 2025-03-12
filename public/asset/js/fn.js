(function($){

	$.fn.serializeObject = function() {
		var retVal = {};
		let field = this.val();
		$.each(this.serializeArray(), function(_, field) {
			retVal[field.name] = field.value;
		});
		return retVal;
	};

}(jQuery));



