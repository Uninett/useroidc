/**
 * ownCloud - useroidc
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later. See the COPYING file.
 *
 * @author Sigmund Augdal <sigmund.augdal@uninett.no>
 * @copyright Sigmund Augdal 2016
 */

(function ($, OC) {

	$(document).ready(function () {
		$('#hello').click(function () {
			alert('Hello from your script file');
		});

		$('#echo').click(function () {
			var url = OC.generateUrl('/apps/useroidc/echo');
			var data = {
				echo: $('#echo-content').val()
			};

			$.post(url, data).success(function (response) {
				$('#echo-result').text(response.echo);
			});

		});
	});

})(jQuery, OC);