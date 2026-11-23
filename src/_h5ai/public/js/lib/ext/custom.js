const marked = require('marked');
const {each, dom} = require('../util');
const server = require('../server');
const event = require('../core/event');
const allsettings = require('../core/settings');


const settings = Object.assign({
    enabled: false
}, allsettings.custom);

// Sanitize HTML to prevent XSS attacks
// Remove dangerous tags and attributes while preserving safe formatting
const sanitize_html = html => {
    // Remove script tags and their content
    html = html.replace(/<script\b[^<]*(?:(?!<\/script>)<[^<]*)*<\/script>/gi, '');
    // Remove event handlers (onclick, onerror, onload, etc.)
    html = html.replace(/\s+on\w+\s*=\s*(?:"[^"]*"|'[^']*'|[^\s>]*)/gi, '');
    // Remove javascript: URLs
    html = html.replace(/(?:href|src|action)\s*=\s*(?:"javascript:[^"]*"|'javascript:[^']*')/gi, '');
    // Remove data: URLs in src attributes (except images)
    html = html.replace(/src\s*=\s*(?:"data:(?!image\/)[^"]*"|'data:(?!image\/)[^']*')/gi, '');
    // Remove iframe, object, embed, form tags
    html = html.replace(/<\/?(?:iframe|object|embed|form|input|textarea|button|select|style)\b[^>]*>/gi, '');
    return html;
};

const update = (data, key) => {
    const $el = dom(`#content-${key}`);

    if (data && data[key].content) {
        let content = data[key].content;
        if (data[key].type === 'md') {
            content = marked(content);
        }
        // Sanitize HTML content to prevent XSS
        content = sanitize_html(content);
        $el.html(content).show();
    } else {
        $el.hide();
    }
};

const onLocationChanged = item => {
    server.request({action: 'get', custom: item.absHref}).then(response => {
        const data = response && response.custom;
        each(['header', 'footer'], key => update(data, key));
    });
};

const init = () => {
    if (!settings.enabled) {
        return;
    }

    dom('<div id="content-header"></div>').hide().preTo('#content');
    dom('<div id="content-footer"></div>').hide().appTo('#content');

    event.sub('location.changed', onLocationChanged);
};


init();
