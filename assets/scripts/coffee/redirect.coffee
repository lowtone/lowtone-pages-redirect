angular = angular

@RedirectCtrl = ($scope, $http) ->
	$scope.type = 'no_redirect'

	$scope
	
@RedirectCtrl.$inject = ['$scope', '$http']