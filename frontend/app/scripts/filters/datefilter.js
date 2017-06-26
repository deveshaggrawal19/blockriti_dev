angular.module('taurus').filter('serverDate',function($filter){

		return function(date,format){
			 var date = new Date(date);
			 format === undefined ? format = 'yyyy-MM-dd': format;
			 return  $filter('date')(date,format);
		}
});