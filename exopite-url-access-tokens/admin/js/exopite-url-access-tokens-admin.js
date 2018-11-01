(function ($) {
    'use strict';

    $(function () {

        $('.show-hide-tokens-js').on('click', function () {
            $('#show-hide-tokens-js').toggle();
        });

        $('.exopite-sof-group').on('exopite-sof-field-group-item-added-after', function (event, $cloned) {
            // https://stackoverflow.com/questions/1349404/generate-random-string-characters-in-javascript/24810220#24810220
            var ran32 = new Array(32).join().replace(/(.|$)/g, function () { return ((Math.random() * 36) | 0).toString(36); });
            $cloned.find('.token-hash').val(ran32);
        });

        var $accessList = $('.exopite-post-access-tokens-access-list');

        // console.log( 'juuhuu' );

        function exopitePATAJAX(args) {

            //https://stackoverflow.com/questions/894860/set-a-default-parameter-value-for-a-javascript-function/33734301#33734301

            var $element = $('.' + args.fn);

            var ajaxData = {
                'action': args.fn,
                'args': args,
                // 'whatever': ajax_object.we_value      // We pass php values differently!
            };

            if ($element.attr('data-id')) ajaxData.postId = $element.data('id');
            if ($element.attr('data-nonce')) ajaxData.nonce = $element.data('nonce');

            // console.log( 'ajaxData: ' + JSON.stringify( ajaxData ) );

            $.ajax({
                cache: false,
                type: "POST",
                url: ajaxurl,
                data: ajaxData,
                beforeSend: function () {
                    $element.addClass('loading');
                },
                success: function (result) {

                    // console.log( 'result: ' + result );
                    $element.html(result);

                },
                error: function (xhr, status, error) {

                    // On connection error
                    console.log('Error: ' + xhr.responseText);

                },
                complete: function () {
                    $element.removeClass('loading');
                }
            });

        }

        if ($accessList.length) {

            console.log('Accesslist');

            var args = {
                'fn': 'exopite-post-access-tokens-access-list',
                'page': '1',
                'status': 'success',
                'sort': 'time',
            };
            exopitePATAJAX(args);

            var timer;
            var timeout = 1000;

            $($accessList).on('propertychange change keyup input paste', '.js-token-list-search', function (event) {

                // event.preventDefault();
                event.stopPropagation();

                clearTimeout(timer);
                var value = $(this).val();

                if (value.length > 2 || value.length == 0) {

                    timer = setTimeout(function () {

                        var args = {
                            'fn': 'exopite-post-access-tokens-access-list',
                            'page': $accessList.find('.token-log-pages .current').data('page'),
                            'status': $accessList.find('.js-token-type-wrapper').data('status'),
                            'sort': $accessList.find('.js-token-log').data('sort'),
                            'order': $accessList.find('.js-token-log').data('order'),
                            'search': value,
                        };

                        exopitePATAJAX(args);

                    }, timeout);

                }

            });

            $($accessList).on('click', '.js-token-type', function (event) {

                var args = {
                    'fn': 'exopite-post-access-tokens-access-list',
                    'page': $accessList.find('.token-log-pages .current').data('page'),
                    'status': $(this).data('status'),
                    'sort': $accessList.find('.js-token-log').data('sort'),
                    'order': $accessList.find('.js-token-log').data('order'),
                    'search': $accessList.find('.js-token-list-search').val(),
                };
                exopitePATAJAX(args);

            });

            $($accessList).on('click', '.js-token-clear-logs', function (event) {
                // console.log( 'crear all logs' );

                event.preventDefault();

                swal({

                    title: "Are you sure?",
                    text: $(this).data('confirm'),
                    icon: "warning",
                    buttons: true,
                    dangerMode: true,

                }).then((willDelete) => {

                    if (willDelete) {

                        // var post_id = $(this).parents('.exopite-post-access-tokens-access-list').data('id');

                        var ajaxData = {
                            'action': 'exopite-post-access-tokens-delete-access-list',
                            'nonce': $(this).parents('.exopite-post-access-tokens-access-list').data('nonce'),
                            'post_id': 'all',
                            // 'whatever': ajax_object.we_value      // We pass php values differently!
                        };

                        // var post_id = $( this ).parents( '.exopite-post-access-tokens-access-list' ).data( 'id' );

                        // if ( typeof post_id !== "undefined" ) {
                        //     ajaxData.post_id = post_id;
                        // }

                        // var nonce = $( this ).parents( '.exopite-post-access-tokens-access-list' ).data( 'nonce' );
                        // if ( $element.attr( 'data-nonce' ) ) ajaxData.nonce = $element.data( 'nonce' );

                        // console.log( 'ajaxData: ' + JSON.stringify( ajaxData ) );

                        // console.log( 'post_id: ' + post_id );
                        // console.log( 'nonce: ' + nonce );

                        $.ajax({
                            cache: false,
                            type: "POST",
                            url: ajaxurl,
                            data: ajaxData,
                            beforeSend: function () {
                                $accessList.addClass('loading');
                            },
                            success: function (result) {

                                console.log('result: ' + result);
                                // $element.html( result );
                                var args = {
                                    'fn': 'exopite-post-access-tokens-access-list',
                                    'page': '1',
                                    'status': 'success',
                                    'sort': 'time',
                                    'order': 'DESC',
                                    'search': '',
                                };
                                exopitePATAJAX(args);

                            },
                            error: function (xhr, status, error) {

                                // On connection error
                                console.log('Error: ' + xhr.responseText);
                                $accessList.removeClass('loading');

                            },
                            // complete: function () {
                            //     $accessList.removeClass('loading');
                            // }
                        });
                    }

                });

            });

            $($accessList).on('click', '.js-token-page', function (event) {

                var args = {
                    'fn': 'exopite-post-access-tokens-access-list',
                    'page': $(this).data('page'),
                    'status': $accessList.find('.js-token-type-wrapper').data('status'),
                    'sort': $accessList.find('.js-token-log').data('sort'),
                    'order': $accessList.find('.js-token-log').data('order'),
                    'search': $accessList.find('.js-token-list-search').val(),
                };
                exopitePATAJAX(args);

            });

            $($accessList).on('click', '.js-token-sort', function (event) {

                var sort = $(this).data('sort');
                var sort_before = $accessList.find('.js-token-log').data('sort')
                var order = $accessList.find('.js-token-log').data('order');

                if (sort == sort_before) order = (order == 'DESC') ? 'ASC' : 'DESC';

                var args = {
                    'fn': 'exopite-post-access-tokens-access-list',
                    'page': $accessList.find('.token-log-pages .current').data('page'),
                    'status': $accessList.find('.js-token-type-wrapper').data('status'),
                    'sort': sort,
                    'order': order,
                    'search': $accessList.find('.js-token-list-search').val(),
                };
                exopitePATAJAX(args);

            });

        }



    });

})(jQuery);
