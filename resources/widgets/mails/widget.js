(function($) {

    var csscls = PhpDebugBar.utils.makecsscls('phpdebugbar-widgets-');

    /**
     * Widget for the displaying mails data
     *
     * Options:
     *  - data
     */
    var MailsWidget = PhpDebugBar.Widgets.MailsWidget = PhpDebugBar.Widget.extend({

        className: csscls('mails'),

        render: function() {
            this.$list = new  PhpDebugBar.Widgets.ListWidget({ itemRenderer: function(li, mail) {
                    $('<span />').addClass(csscls('subject')).text(mail.subject).appendTo(li);
                    $('<span />').addClass(csscls('to')).text(mail.to).appendTo(li);
                    if (mail.body || mail.html) {
                        var header = $('<span />').addClass(csscls('filename')).text('');
                        $('<a title="Mail Preview">View Mail</a>').on('click', function () {
                            var popup = window.open('about:blank', 'Mail Preview', 'width=650,height=440,scrollbars=yes');
                            var documentToWriteTo = popup.document;
                            var headers = !mail.headers ? '' : $('<pre style="border: 1px solid #ddd; padding: 5px;" />')
                                .append($('<code />').text(mail.headers));

                            var body = $('<pre style="border: 1px solid #ddd; padding: 5px;" />').text(mail.body)
                            var html = null;
                            if (mail.html) {
                                body = $('<details />').append($('<summary>Text version</summary>')).append(body);
                                html = $('<iframe width="100%" height="400px" sandbox="" referrerpolicy="no-referrer"/>').attr("srcdoc", mail.html)
                            }

                            documentToWriteTo.open();
                            documentToWriteTo.write(headers.prop('outerHTML') + body.prop('outerHTML') + (html ? html.prop('outerHTML') : ''));
                            documentToWriteTo.close();
                        }).addClass(csscls('editor-link')).appendTo(header);

                        header.appendTo(li);
                    }

                    if (mail.headers) {
                        var headers = $('<pre />').addClass(csscls('headers')).appendTo(li);
                        $('<code />').text(mail.headers).appendTo(headers);
                        li.click(function() {
                            if (headers.is(':visible')) {
                                headers.hide();
                            } else {
                                headers.show();
                            }
                        });
                    }
                }});
            this.$list.$el.appendTo(this.$el);

            this.bindAttr('data', function(data) {
                this.$list.set('data', data);
            });
        }

    });

})(PhpDebugBar.$);
