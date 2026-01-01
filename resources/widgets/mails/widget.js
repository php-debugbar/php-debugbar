(function () {
    const csscls = PhpDebugBar.utils.makecsscls('phpdebugbar-widgets-');

    /**
     * Widget for the displaying mails data
     *
     * Options:
     *  - data
     */
    class MailsWidget extends PhpDebugBar.Widget {
        get className() {
            return csscls('mails');
        }

        render() {
            this.list = new PhpDebugBar.Widgets.ListWidget({ itemRenderer(li, mail) {
                const subject = document.createElement('span');
                subject.classList.add(csscls('subject'));
                subject.textContent = mail.subject;
                li.append(subject);

                const to = document.createElement('span');
                to.classList.add(csscls('to'));
                to.textContent = mail.to;
                li.append(to);

                if (mail.body || mail.html) {
                    const header = document.createElement('span');
                    header.classList.add(csscls('filename'));
                    header.textContent = '';

                    const link = document.createElement('a');
                    link.setAttribute('title', 'Mail Preview');
                    link.textContent = 'View Mail';
                    link.classList.add(csscls('editor-link'));
                    link.addEventListener('click', () => {
                        const popup = window.open('about:blank', 'Mail Preview', 'width=650,height=440,scrollbars=yes');
                        const documentToWriteTo = popup.document;

                        let headersHTML = '';
                        if (mail.headers) {
                            const headersPre = document.createElement('pre');
                            headersPre.style.border = '1px solid #ddd';
                            headersPre.style.padding = '5px';
                            const headersCode = document.createElement('code');
                            headersCode.textContent = mail.headers;
                            headersPre.append(headersCode);
                            headersHTML = headersPre.outerHTML;
                        }

                        const bodyPre = document.createElement('pre');
                        bodyPre.style.border = '1px solid #ddd';
                        bodyPre.style.padding = '5px';
                        bodyPre.textContent = mail.body;

                        let bodyHTML = bodyPre.outerHTML;
                        let htmlIframeHTML = '';
                        if (mail.html) {
                            const details = document.createElement('details');
                            const summary = document.createElement('summary');
                            summary.textContent = 'Text version';
                            details.append(summary);
                            details.append(bodyPre);
                            bodyHTML = details.outerHTML;

                            const htmlIframe = document.createElement('iframe');
                            htmlIframe.setAttribute('width', '100%');
                            htmlIframe.setAttribute('height', '400px');
                            htmlIframe.setAttribute('sandbox', '');
                            htmlIframe.setAttribute('referrerpolicy', 'no-referrer');
                            htmlIframe.setAttribute('srcdoc', mail.html);
                            htmlIframeHTML = htmlIframe.outerHTML;
                        }

                        documentToWriteTo.open();
                        documentToWriteTo.write(headersHTML + bodyHTML + htmlIframeHTML);
                        documentToWriteTo.close();
                    });
                    header.append(link);
                    li.append(header);
                }

                if (mail.headers) {
                    const headers = document.createElement('pre');
                    headers.classList.add(csscls('headers'));

                    const code = document.createElement('code');
                    code.textContent = mail.headers;
                    headers.append(code);
                    headers.hidden = true;
                    li.append(headers);

                    li.addEventListener('click', () => {
                        headers.hidden = !headers.hidden;
                    });
                }
            } });
            this.el.append(this.list.el);

            this.bindAttr('data', function (data) {
                this.list.set('data', data);
            });
        }
    }
    PhpDebugBar.Widgets.MailsWidget = MailsWidget;
})();
