angular = @angular

settings = @lowtone_pages_redirect

@RedirectCtrl = ($scope) ->
	angular.extend $scope, settings