(function($) {
    //dom ready
    $(function() {
        
        //add required classes
        $('#publishForm .publish_field').each(function(){
            if($(this).find('.hide_field em.required').length > 0) {
                $(this).find('fieldset.holder input').addClass('validate[required]');
            }
        });

        //execute the validator
        $('form#publishForm').validationEngine('attach', {
            promptPosition : "topLeft", 
            scroll: false,
            //prettySelect : true,
            validateNonVisibleFields : true,
            
            onValidationComplete : function (form, status) {

                //reset all the error icons
                $('.icon-warning-sign').remove();

                //add icons if needed
                form.find('input').each(function(){
                    if($(this).parent().find('.formError').length == 1) {
                        var menu_id = '#menu_'+$(this).parents('.main_tab').attr('id');
                        var $menu_elem = $(menu_id);

                        //show icon if needed
                        if($menu_elem.find('i').length == 0) {
                            $menu_elem.find('a').append('<i class="icon-warning-sign"></i>');
                        } 
                    }
                });               
            }
            
        });

        // when the tabs are clicked, we need to position the promt messages again
        $('.content_tab').watch('class', function(propName, oldVal, newVal){
            $('form#publishForm').validationEngine('updatePromptsPosition');
        });

    });

    //monitor dom changes
    jQuery.fn.watch = function( id, fn ) {
     
        return this.each(function(){
     
            var self = this;
            var oldVal = $(self).attr(id);

            $(self).data(
                'watch_timer',
                setInterval(function(){
                    if ($(self).attr(id) !== oldVal) {
                        fn.call(self, id, oldVal, $(self).attr(id));
                        oldVal = $(self).attr(id);
                    }
                }, 100)
            );
     
        });
     
        return self;
    };
     
    jQuery.fn.unwatch = function( id ) {
     
        return this.each(function(){
            clearInterval( $(this).data('watch_timer') );
        });
     
    };

})(jQuery);