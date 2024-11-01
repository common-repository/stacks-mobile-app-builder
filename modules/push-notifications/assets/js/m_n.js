jQuery( function( $ ) {

        /**
         * Setting from server 
         * @type object
         */
        var settings = {
            reset_action:       stacks_manual_notificaiton.reset_action,
            send_action:        stacks_manual_notificaiton.send_action,
            ajax_url:           stacks_manual_notificaiton.ajax_url,
            customers_num:      stacks_manual_notificaiton.customers_num,
            search_customers:   stacks_manual_notificaiton.search_customers
        } ;

        var progress_bar = {
            
            update_value:function( value )
            {
                $('.progress-bar').width( value ) ;
                $('.progress-bar').text( value ) ;
            },
            
            reset:function()
            {
                progress_bar.update_value('0%');
                progress_bar.hide_progress_bar_success();  
                progress_bar.show_progress_bar_statistics();
                progress_bar.update_offset_num('0');
            },
            
            /**
             * get number of users either selected or that comes from server 
             * 
             * @returns {settings.customers_num|users_tracker.count_selected_users}
             */
            get_number_of_users:function()
            {
                // get selected users number
                users_tracker_count = users_tracker.count_selected_users() ;
                
                //if selected is empty get what comes from server 
                if( users_tracker_count == 0 )
                {
                    return settings.customers_num ;
                }
                else
                {
                    return users_tracker_count ;
                }
            },
            
            /**
             * calculate width by dividing current users number with the 
             * @param {int} offset
             * @returns {String}
             */
            calculate_width_from_offset:function( offset )
            {
                users_num = progress_bar.get_number_of_users() ;
                
                if( offset == users_num )
                {
                    return '100%';
                }
                
                return parseInt( ( offset / users_num ) * 100 )+"%" ;
            },
            
            done:function()
            {
                progress_bar.show_progress_bar_success(); 
                progress_bar.hide_progress_bar_statistics();
                progress_bar.update_value( progress_bar.calculate_width_from_offset( progress_bar.get_number_of_users() ) );
            },
            
            show_progress_bar:function()
            {
                $('.progress-bar-container' ).show();
            },
            
            hide_progress_bar:function()
            {
                $('.progress-bar-container' ).hide();
            },
            
            update_offset_num:function( offset )
            {
                $('.offset_num').text( offset ) ;
            },
            
            show_progress_bar_statistics:function()
            {
                $('.progress-bar-statistics' ).show();
            },
            
            show_progress_bar_success:function()
            {
                $('.progress-bar-success' ).show();
            },
            
            hide_progress_bar_success:function()
            {
                $('.progress-bar-success' ).hide();
            },
            
            hide_progress_bar_statistics:function()
            {
                $('.progress-bar-statistics' ).hide();
            }
        };


        var users_tracker = {
            
            users:[],
            template: "user-tr-column",
            tbody : "#the-list",
            
            init: function()
            {
                $(document).on('click','.cancel-user-button',function(){
                    id = $(this).data('id');
                    users_tracker.remove_user( id );
                });
            },
            
            count_selected_users:function()
            {
                return users_tracker.users.length ;
            },
            
            add_user:function( user )
            {
                users_tracker.users.push( user ) ; 
            },
            
            remove_user:function( id )
            {
                users_tracker.remove_user_record( id );
                users_tracker.remove_user_row( id );
            },
            
            remove_user_record:function( id )
            {
                users_tracker.users = users_tracker.users.filter( function( user ){
                    if( user.id === id )
                    {
                        return false ;
                    }
                    return true ;
                }) ;
            },
            
            remove_user_row:function( id )
            {
                $( "#user-"+id ).fadeOut().remove(); 
            },
            
            get_users_ids:function()
            {
                return users_tracker.users.map( function( user ){
                   return user.id ; 
                });
            },
            
            render_selected_users_count()
            {
                $('.users_num').text( progress_bar.get_number_of_users() );
            },
            
            render_new_user:function( user )
            {
                templateHtml = document.getElementById( users_tracker.template ).innerHTML;
                
                listHtml = "";

                listHtml += templateHtml.replace(/{{id}}/g, user.id )
                    .replace(/{{name}}/g, user.name )
                    .replace(/{{username}}/g, user.username )
                    .replace(/{{email}}/g, user.email );

                $( users_tracker.tbody ).append( listHtml );
                
                users_tracker.render_selected_users_count();
            },
            
            reset:function()
            {
                $( users_tracker.tbody ).empty();
                users_tracker.users = [] ;
            }
            
        }

        /**
         * User search 
         * @type object
         */
        var stacks_manual_notification_usersearch = {
            
            search_field: "#users_search",
            
            init:function()
            {                    
                $( stacks_manual_notification_usersearch.search_field ).autocomplete({
                    source: function( request, response ) 
                    {
                        $.get( 
                            stacks_manual_notificaiton.ajax_url ,
                            {
                                action: settings.search_customers,
                                excluded_ids: ( users_tracker.get_users_ids() ).join(),
                                name: request.term
                            } 
                            ).always( function() {
                                    $( stacks_manual_notification_usersearch.search_field ).removeClass( 'ui-autocomplete-loading' );
                            } ).done( function( data ) {
                                    var users = [];

                                    if ( data.success ) {
                                            response( data.data );
                                    } else {
                                            response( users );
                                    }
                            } );
                    },
                    minLength: 3,
                    select: function( event, ui ) {
                        users_tracker.add_user( ui.item );
                        users_tracker.render_new_user( ui.item );
                        $(".send-notification-wrapper").removeClass('full-width ').addClass('half-width-flex').width("40%");
                        $(".users-tracker-wrapper").show().width("60%");
                        // empty search field
                        this.value = "";
                        return false;
                    }
              } );
            }
            
        }
        
	/**
	 * Manual Notifcation reset actions
	 */
	var stacks_manual_notification_reset = {

                /**
		 * Initialize action listeners
		 */
                init:function(){
                    $('.reset').on( 'click', this.reset );
                },
                
                /**
                 * respond to listener and perform action call
                 * @param {type} e
                 * @returns {void}
                 */
		reset: function( e ) {
                    e.preventDefault();
                    progress_bar.reset();
                    progress_bar.hide_progress_bar(); 
                    stacks_manual_notification_reset.perform_reset_action( e );
                    users_tracker.reset();
		},

                /**
                 * send ajax request to server to forget old operation
                 * @param {type} e
                 * @returns {void}
                 */
		perform_reset_action: function( e ) {
                    jQuery.post(
                            settings.ajax_url, {
                                action : settings.reset_action
                            }, function (response) {
                                if ( response.success == true ){
                                    $('.reset').hide();
                                    $('.submit-button').removeClass( 'half-width' ).addClass('full-width').text('send');
                                    $('.form-input').removeProp('disabled').val('');
                                }else{
                                    alert( 'error from server could not place request please refresh the page');
                                }
                           }
                    );
		}
	};

        /**
	 * Manual Notifcation send actions
	 */
        var stacks_manual_notification_send = {
            /**
             * Initialize action listeners
             */
            init: function(){
                $('.submit-button').on('click' , this.send ) ;
            },
            
            /**
             * start getting message and title and begin process
             * @param {type} e
             */
            send: function( e ) {
                e.preventDefault();
                
                var message = $('#notification_body').val();
                var title   = $('#title').val();
                
                progress_bar.show_progress_bar();
                progress_bar.reset();
                users_tracker.render_selected_users_count();
                stacks_manual_notification_send.start( message, title );
            },
            
            start: function( message, title )
            {
                jQuery.post(
                        settings.ajax_url, {
                            action  : settings.send_action,
                            message : message,
                            title   : title,
                            selected: ( users_tracker.get_users_ids() ).join()
                        }, function (response) {
                            if ( response.continue === true )
                            {
                                progress_bar.update_value( progress_bar.calculate_width_from_offset( response.offset ) ) ;
                                progress_bar.update_offset_num( response.offset );
                                stacks_manual_notification_send.start( message, title );
                            }
                            else
                            {
                                progress_bar.done(); 
                                users_tracker.reset();
                                $('.form-input').removeProp('disabled').val('');
                                $('.reset').hide();
                                $('.submit-button').removeClass( 'half-width' ).addClass('full-width').text('send');
                            }
                       }
                );
            }
            
        };

        /**
	 * Begin Listening
	 */
	users_tracker.init();
	stacks_manual_notification_reset.init();
	stacks_manual_notification_send.init();
	stacks_manual_notification_usersearch.init();
});
