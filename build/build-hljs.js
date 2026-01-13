// Build custom highlight.js bundle with specific languages
import hljs from 'highlight.js/lib/core';
import php from 'highlight.js/lib/languages/php';
import phpTemplate from 'highlight.js/lib/languages/php-template';
import javascript from 'highlight.js/lib/languages/javascript';
import sql from 'highlight.js/lib/languages/sql';
import shell from 'highlight.js/lib/languages/shell';
import css from 'highlight.js/lib/languages/css';
import plaintext from 'highlight.js/lib/languages/plaintext';
import xml from 'highlight.js/lib/languages/xml';
import yaml from 'highlight.js/lib/languages/yaml';

hljs.registerLanguage('php', php);
hljs.registerLanguage('php-template', phpTemplate);
hljs.registerLanguage('javascript', javascript);
hljs.registerLanguage('sql', sql);
hljs.registerLanguage('shell', shell);
hljs.registerLanguage('css', css);
hljs.registerLanguage('plaintext', plaintext);
hljs.registerLanguage('xml', xml);
hljs.registerLanguage('yaml', yaml);


const sqlLang = hljs.getLanguage('sql');

//Extend sql keywords
sqlLang.keywords.keyword = Array.from(new Set([
    ...sqlLang.keywords.keyword,
    'if','ifnull','limit','aes_decrypt','aes_encrypt','ascii','bin','bit_and','bit_count','bit_length','bit_or','bit_xor','coercibility','concat','group_concat','concat_ws','connection_id','conv','curdate','curtime','database','date_add','date_format','date_sub','dayname','dayofmonth','dayofweek','dayofyear','elt','export_set','field','find_in_set','format','from_base64','from_days','from_unixtime','get_lock','greatest','hex','ifnull','inet_aton','inet_ntoa','instr','isnull','last_insert_id','least','lpad','ltrim','make_set','md5','monthname','now','oct','ord','password','quote','release_lock','repeat','replace','reverse','rpad','rtrim','sec_to_time','sha1','sha2','sleep','soundex','space','strcmp','str_to_date','substr','sysdate','time_format','time_to_sec','to_base64','to_days','unix_timestamp','updatexml','version','week','weekday','yearweek','length','substring_index','json_unquote','json_extract','json_contains'
]));
sqlLang.keywords.type = Array.from(new Set([
    ...sqlLang.keywords.type,
    'longtext',
]));

// Configure to use custom CSS class prefix
hljs.configure({ classPrefix: 'phpdebugbar-hljs-' });

globalThis.phpdebugbar_hljs = hljs.default;
