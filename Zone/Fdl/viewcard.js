/**
 * @author Anakeen
 * @license http://www.fsf.org/licensing/licenses/agpl-3.0.html GNU Affero General Public License
 */

// to adjust height of body in edit card in fixed positionning

$( document ).ready(function() {
    function fixHeader() {
        var $header=$("#fixtablehead");
        var height=$header.height();
        if (height > 0 && $header.css("position")==="fixed") {
            $('body').css("margin-top", height+"px");
        }
    };

    fixHeader();

    $(window).on("resize", function () {
        fixHeader();
    })
});