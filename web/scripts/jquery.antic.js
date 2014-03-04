/* AntiC Website JQuery Methods
 * Used for Global JQuery Buttons
 * and listeners
 */
$(function(){

    // This would be best done with just AngularJS to not mix the two
    $('.btnDisableUser').click(function(){
        var userID = $(this).attr('id').split('-')[1];
        $.post("/console/user/"+userID+"/enable");
    });

});