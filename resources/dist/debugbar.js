(()=>{var mt=Object.create;var Ae=Object.defineProperty;var Et=Object.getOwnPropertyDescriptor;var yt=Object.getOwnPropertyNames;var wt=Object.getPrototypeOf,xt=Object.prototype.hasOwnProperty;var vt=(e,t)=>()=>(t||e((t={exports:{}}).exports,t),t.exports);var Nt=(e,t,n,s)=>{if(t&&typeof t=="object"||typeof t=="function")for(let g of yt(t))!xt.call(e,g)&&g!==n&&Ae(e,g,{get:()=>t[g],enumerable:!(s=Et(t,g))||s.enumerable});return e};var St=(e,t,n)=>(n=e!=null?mt(wt(e)):{},Nt(t||!e||!e.__esModule?Ae(n,"default",{value:e,enumerable:!0}):n,e));var Ye=vt((kn,Xe)=>{function Ue(e){return e instanceof Map?e.clear=e.delete=e.set=function(){throw new Error("map is read-only")}:e instanceof Set&&(e.add=e.clear=e.delete=function(){throw new Error("set is read-only")}),Object.freeze(e),Object.getOwnPropertyNames(e).forEach(t=>{let n=e[t],s=typeof n;(s==="object"||s==="function")&&!Object.isFrozen(n)&&Ue(n)}),e}var ue=class{constructor(t){t.data===void 0&&(t.data={}),this.data=t.data,this.isMatchIgnored=!1}ignoreMatch(){this.isMatchIgnored=!0}};function Pe(e){return e.replace(/&/g,"&amp;").replace(/</g,"&lt;").replace(/>/g,"&gt;").replace(/"/g,"&quot;").replace(/'/g,"&#x27;")}function q(e,...t){let n=Object.create(null);for(let s in e)n[s]=e[s];return t.forEach(function(s){for(let g in s)n[g]=s[g]}),n}var Ot="</span>",Me=e=>!!e.scope,Rt=(e,{prefix:t})=>{if(e.startsWith("language:"))return e.replace("language:","language-");if(e.includes(".")){let n=e.split(".");return[`${t}${n.shift()}`,...n.map((s,g)=>`${s}${"_".repeat(g+1)}`)].join(" ")}return`${t}${e}`},me=class{constructor(t,n){this.buffer="",this.classPrefix=n.classPrefix,t.walk(this)}addText(t){this.buffer+=Pe(t)}openNode(t){if(!Me(t))return;let n=Rt(t.scope,{prefix:this.classPrefix});this.span(n)}closeNode(t){Me(t)&&(this.buffer+=Ot)}value(){return this.buffer}span(t){this.buffer+=`<span class="${t}">`}},Ie=(e={})=>{let t={children:[]};return Object.assign(t,e),t},Ee=class e{constructor(){this.rootNode=Ie(),this.stack=[this.rootNode]}get top(){return this.stack[this.stack.length-1]}get root(){return this.rootNode}add(t){this.top.children.push(t)}openNode(t){let n=Ie({scope:t});this.add(n),this.stack.push(n)}closeNode(){if(this.stack.length>1)return this.stack.pop()}closeAllNodes(){for(;this.closeNode(););}toJSON(){return JSON.stringify(this.rootNode,null,4)}walk(t){return this.constructor._walk(t,this.rootNode)}static _walk(t,n){return typeof n=="string"?t.addText(n):n.children&&(t.openNode(n),n.children.forEach(s=>this._walk(t,s)),t.closeNode(n)),t}static _collapse(t){typeof t!="string"&&t.children&&(t.children.every(n=>typeof n=="string")?t.children=[t.children.join("")]:t.children.forEach(n=>{e._collapse(n)}))}},ye=class extends Ee{constructor(t){super(),this.options=t}addText(t){t!==""&&this.add(t)}startScope(t){this.openNode(t)}endScope(){this.closeNode()}__addSublanguage(t,n){let s=t.root;n&&(s.scope=`language:${n}`),this.add(s)}toHTML(){return new me(this,this.options).value()}finalize(){return this.closeAllNodes(),!0}};function ae(e){return e?typeof e=="string"?e:e.source:null}function ze(e){return J("(?=",e,")")}function Tt(e){return J("(?:",e,")*")}function kt(e){return J("(?:",e,")?")}function J(...e){return e.map(n=>ae(n)).join("")}function At(e){let t=e[e.length-1];return typeof t=="object"&&t.constructor===Object?(e.splice(e.length-1,1),t):{}}function xe(...e){return"("+(At(e).capture?"":"?:")+e.map(s=>ae(s)).join("|")+")"}function $e(e){return new RegExp(e.toString()+"|").exec("").length-1}function Mt(e,t){let n=e&&e.exec(t);return n&&n.index===0}var It=/\[(?:[^\\\]]|\\.)*\]|\(\??|\\([1-9][0-9]*)|\\./;function ve(e,{joinWith:t}){let n=0;return e.map(s=>{n+=1;let g=n,b=ae(s),a="";for(;b.length>0;){let i=It.exec(b);if(!i){a+=b;break}a+=b.substring(0,i.index),b=b.substring(i.index+i[0].length),i[0][0]==="\\"&&i[1]?a+="\\"+String(Number(i[1])+g):(a+=i[0],i[0]==="("&&n++)}return a}).map(s=>`(${s})`).join(t)}var Ct=/\b\B/,Ge="[a-zA-Z]\\w*",Ne="[a-zA-Z_]\\w*",He="\\b\\d+(\\.\\d+)?",Fe="(-?)(\\b0[xX][a-fA-F0-9]+|(\\b\\d+(\\.\\d*)?|\\.\\d+)([eE][-+]?\\d+)?)",Ke="\\b(0b[01]+)",Lt="!|!=|!==|%|%=|&|&&|&=|\\*|\\*=|\\+|\\+=|,|-|-=|/=|/|:|;|<<|<<=|<=|<|===|==|=|>>>=|>>=|>=|>>>|>>|>|\\?|\\[|\\{|\\(|\\^|\\^=|\\||\\|=|\\|\\||~",Dt=(e={})=>{let t=/^#![ ]*\//;return e.binary&&(e.begin=J(t,/.*\b/,e.binary,/\b.*/)),q({scope:"meta",begin:t,end:/$/,relevance:0,"on:begin":(n,s)=>{n.index!==0&&s.ignoreMatch()}},e)},se={begin:"\\\\[\\s\\S]",relevance:0},Bt={scope:"string",begin:"'",end:"'",illegal:"\\n",contains:[se]},Ut={scope:"string",begin:'"',end:'"',illegal:"\\n",contains:[se]},Pt={begin:/\b(a|an|the|are|I'm|isn't|don't|doesn't|won't|but|just|should|pretty|simply|enough|gonna|going|wtf|so|such|will|you|your|they|like|more)\b/},ge=function(e,t,n={}){let s=q({scope:"comment",begin:e,end:t,contains:[]},n);s.contains.push({scope:"doctag",begin:"[ ]*(?=(TODO|FIXME|NOTE|BUG|OPTIMIZE|HACK|XXX):)",end:/(TODO|FIXME|NOTE|BUG|OPTIMIZE|HACK|XXX):/,excludeBegin:!0,relevance:0});let g=xe("I","a","is","so","us","to","at","if","in","it","on",/[A-Za-z]+['](d|ve|re|ll|t|s|n)/,/[A-Za-z]+[-][a-z]+/,/[A-Za-z][a-z]{2,}/);return s.contains.push({begin:J(/[ ]+/,"(",g,/[.]?[:]?([.][ ]|[ ])/,"){3}")}),s},zt=ge("//","$"),$t=ge("/\\*","\\*/"),Gt=ge("#","$"),Ht={scope:"number",begin:He,relevance:0},Ft={scope:"number",begin:Fe,relevance:0},Kt={scope:"number",begin:Ke,relevance:0},jt={scope:"regexp",begin:/\/(?=[^/\n]*\/)/,end:/\/[gimuy]*/,contains:[se,{begin:/\[/,end:/\]/,relevance:0,contains:[se]}]},Wt={scope:"title",begin:Ge,relevance:0},Zt={scope:"title",begin:Ne,relevance:0},qt={begin:"\\.\\s*"+Ne,relevance:0},Xt=function(e){return Object.assign(e,{"on:begin":(t,n)=>{n.data._beginMatch=t[1]},"on:end":(t,n)=>{n.data._beginMatch!==t[1]&&n.ignoreMatch()}})},le=Object.freeze({__proto__:null,APOS_STRING_MODE:Bt,BACKSLASH_ESCAPE:se,BINARY_NUMBER_MODE:Kt,BINARY_NUMBER_RE:Ke,COMMENT:ge,C_BLOCK_COMMENT_MODE:$t,C_LINE_COMMENT_MODE:zt,C_NUMBER_MODE:Ft,C_NUMBER_RE:Fe,END_SAME_AS_BEGIN:Xt,HASH_COMMENT_MODE:Gt,IDENT_RE:Ge,MATCH_NOTHING_RE:Ct,METHOD_GUARD:qt,NUMBER_MODE:Ht,NUMBER_RE:He,PHRASAL_WORDS_MODE:Pt,QUOTE_STRING_MODE:Ut,REGEXP_MODE:jt,RE_STARTERS_RE:Lt,SHEBANG:Dt,TITLE_MODE:Wt,UNDERSCORE_IDENT_RE:Ne,UNDERSCORE_TITLE_MODE:Zt});function Yt(e,t){e.input[e.index-1]==="."&&t.ignoreMatch()}function Vt(e,t){e.className!==void 0&&(e.scope=e.className,delete e.className)}function Qt(e,t){t&&e.beginKeywords&&(e.begin="\\b("+e.beginKeywords.split(" ").join("|")+")(?!\\.)(?=\\b|\\s)",e.__beforeBegin=Yt,e.keywords=e.keywords||e.beginKeywords,delete e.beginKeywords,e.relevance===void 0&&(e.relevance=0))}function Jt(e,t){Array.isArray(e.illegal)&&(e.illegal=xe(...e.illegal))}function en(e,t){if(e.match){if(e.begin||e.end)throw new Error("begin & end are not supported with match");e.begin=e.match,delete e.match}}function tn(e,t){e.relevance===void 0&&(e.relevance=1)}var nn=(e,t)=>{if(!e.beforeMatch)return;if(e.starts)throw new Error("beforeMatch cannot be used with starts");let n=Object.assign({},e);Object.keys(e).forEach(s=>{delete e[s]}),e.keywords=n.keywords,e.begin=J(n.beforeMatch,ze(n.begin)),e.starts={relevance:0,contains:[Object.assign(n,{endsParent:!0})]},e.relevance=0,delete n.beforeMatch},rn=["of","and","for","in","not","or","if","then","parent","list","value"],an="keyword";function je(e,t,n=an){let s=Object.create(null);return typeof e=="string"?g(n,e.split(" ")):Array.isArray(e)?g(n,e):Object.keys(e).forEach(function(b){Object.assign(s,je(e[b],t,b))}),s;function g(b,a){t&&(a=a.map(i=>i.toLowerCase())),a.forEach(function(i){let c=i.split("|");s[c[0]]=[b,sn(c[0],c[1])]})}}function sn(e,t){return t?Number(t):on(e)?0:1}function on(e){return rn.includes(e.toLowerCase())}var Ce={},Q=e=>{console.error(e)},Le=(e,...t)=>{console.log(`WARN: ${e}`,...t)},re=(e,t)=>{Ce[`${e}/${t}`]||(console.log(`Deprecated as of ${e}. ${t}`),Ce[`${e}/${t}`]=!0)},de=new Error;function We(e,t,{key:n}){let s=0,g=e[n],b={},a={};for(let i=1;i<=t.length;i++)a[i+s]=g[i],b[i+s]=!0,s+=$e(t[i-1]);e[n]=a,e[n]._emit=b,e[n]._multi=!0}function cn(e){if(Array.isArray(e.begin)){if(e.skip||e.excludeBegin||e.returnBegin)throw Q("skip, excludeBegin, returnBegin not compatible with beginScope: {}"),de;if(typeof e.beginScope!="object"||e.beginScope===null)throw Q("beginScope must be object"),de;We(e,e.begin,{key:"beginScope"}),e.begin=ve(e.begin,{joinWith:""})}}function ln(e){if(Array.isArray(e.end)){if(e.skip||e.excludeEnd||e.returnEnd)throw Q("skip, excludeEnd, returnEnd not compatible with endScope: {}"),de;if(typeof e.endScope!="object"||e.endScope===null)throw Q("endScope must be object"),de;We(e,e.end,{key:"endScope"}),e.end=ve(e.end,{joinWith:""})}}function un(e){e.scope&&typeof e.scope=="object"&&e.scope!==null&&(e.beginScope=e.scope,delete e.scope)}function dn(e){un(e),typeof e.beginScope=="string"&&(e.beginScope={_wrap:e.beginScope}),typeof e.endScope=="string"&&(e.endScope={_wrap:e.endScope}),cn(e),ln(e)}function gn(e){function t(a,i){return new RegExp(ae(a),"m"+(e.case_insensitive?"i":"")+(e.unicodeRegex?"u":"")+(i?"g":""))}class n{constructor(){this.matchIndexes={},this.regexes=[],this.matchAt=1,this.position=0}addRule(i,c){c.position=this.position++,this.matchIndexes[this.matchAt]=c,this.regexes.push([c,i]),this.matchAt+=$e(i)+1}compile(){this.regexes.length===0&&(this.exec=()=>null);let i=this.regexes.map(c=>c[1]);this.matcherRe=t(ve(i,{joinWith:"|"}),!0),this.lastIndex=0}exec(i){this.matcherRe.lastIndex=this.lastIndex;let c=this.matcherRe.exec(i);if(!c)return null;let _=c.findIndex((L,O)=>O>0&&L!==void 0),m=this.matchIndexes[_];return c.splice(0,_),Object.assign(c,m)}}class s{constructor(){this.rules=[],this.multiRegexes=[],this.count=0,this.lastIndex=0,this.regexIndex=0}getMatcher(i){if(this.multiRegexes[i])return this.multiRegexes[i];let c=new n;return this.rules.slice(i).forEach(([_,m])=>c.addRule(_,m)),c.compile(),this.multiRegexes[i]=c,c}resumingScanAtSamePosition(){return this.regexIndex!==0}considerAll(){this.regexIndex=0}addRule(i,c){this.rules.push([i,c]),c.type==="begin"&&this.count++}exec(i){let c=this.getMatcher(this.regexIndex);c.lastIndex=this.lastIndex;let _=c.exec(i);if(this.resumingScanAtSamePosition()&&!(_&&_.index===this.lastIndex)){let m=this.getMatcher(0);m.lastIndex=this.lastIndex+1,_=m.exec(i)}return _&&(this.regexIndex+=_.position+1,this.regexIndex===this.count&&this.considerAll()),_}}function g(a){let i=new s;return a.contains.forEach(c=>i.addRule(c.begin,{rule:c,type:"begin"})),a.terminatorEnd&&i.addRule(a.terminatorEnd,{type:"end"}),a.illegal&&i.addRule(a.illegal,{type:"illegal"}),i}function b(a,i){let c=a;if(a.isCompiled)return c;[Vt,en,dn,nn].forEach(m=>m(a,i)),e.compilerExtensions.forEach(m=>m(a,i)),a.__beforeBegin=null,[Qt,Jt,tn].forEach(m=>m(a,i)),a.isCompiled=!0;let _=null;return typeof a.keywords=="object"&&a.keywords.$pattern&&(a.keywords=Object.assign({},a.keywords),_=a.keywords.$pattern,delete a.keywords.$pattern),_=_||/\w+/,a.keywords&&(a.keywords=je(a.keywords,e.case_insensitive)),c.keywordPatternRe=t(_,!0),i&&(a.begin||(a.begin=/\B|\b/),c.beginRe=t(c.begin),!a.end&&!a.endsWithParent&&(a.end=/\B|\b/),a.end&&(c.endRe=t(c.end)),c.terminatorEnd=ae(c.end)||"",a.endsWithParent&&i.terminatorEnd&&(c.terminatorEnd+=(a.end?"|":"")+i.terminatorEnd)),a.illegal&&(c.illegalRe=t(a.illegal)),a.contains||(a.contains=[]),a.contains=[].concat(...a.contains.map(function(m){return pn(m==="self"?a:m)})),a.contains.forEach(function(m){b(m,c)}),a.starts&&b(a.starts,i),c.matcher=g(c),c}if(e.compilerExtensions||(e.compilerExtensions=[]),e.contains&&e.contains.includes("self"))throw new Error("ERR: contains `self` is not supported at the top-level of a language.  See documentation.");return e.classNameAliases=q(e.classNameAliases||{}),b(e)}function Ze(e){return e?e.endsWithParent||Ze(e.starts):!1}function pn(e){return e.variants&&!e.cachedVariants&&(e.cachedVariants=e.variants.map(function(t){return q(e,{variants:null},t)})),e.cachedVariants?e.cachedVariants:Ze(e)?q(e,{starts:e.starts?q(e.starts):null}):Object.isFrozen(e)?q(e):e}var fn="11.11.1",we=class extends Error{constructor(t,n){super(t),this.name="HTMLInjectionError",this.html=n}},_e=Pe,De=q,Be=Symbol("nomatch"),bn=7,qe=function(e){let t=Object.create(null),n=Object.create(null),s=[],g=!0,b="Could not find the language '{}', did you forget to load/include a language module?",a={disableAutodetect:!0,name:"Plain text",contains:[]},i={ignoreUnescapedHTML:!1,throwUnescapedHTML:!1,noHighlightRe:/^(no-?highlight)$/i,languageDetectRe:/\blang(?:uage)?-([\w-]+)\b/i,classPrefix:"hljs-",cssSelector:"pre code",languages:null,__emitter:ye};function c(r){return i.noHighlightRe.test(r)}function _(r){let u=r.className+" ";u+=r.parentNode?r.parentNode.className:"";let d=i.languageDetectRe.exec(u);if(d){let h=x(d[1]);return h||(Le(b.replace("{}",d[1])),Le("Falling back to no-highlight mode for this block.",r)),h?d[1]:"no-highlight"}return u.split(/\s+/).find(h=>c(h)||x(h))}function m(r,u,d){let h="",w="";typeof u=="object"?(h=r,d=u.ignoreIllegals,w=u.language):(re("10.7.0","highlight(lang, code, ...args) has been deprecated."),re("10.7.0",`Please use highlight(code, options) instead.
https://github.com/highlightjs/highlight.js/issues/2277`),w=r,h=u),d===void 0&&(d=!0);let T={code:h,language:w};M("before:highlight",T);let G=T.result?T.result:L(T.language,T.code,d);return G.code=T.code,M("after:highlight",G),G}function L(r,u,d,h){let w=Object.create(null);function T(o,l){return o.keywords[l]}function G(){if(!p.keywords){S.addText(y);return}let o=0;p.keywordPatternRe.lastIndex=0;let l=p.keywordPatternRe.exec(y),f="";for(;l;){f+=y.substring(o,l.index);let E=K.case_insensitive?l[0].toLowerCase():l[0],k=T(p,E);if(k){let[j,ht]=k;if(S.addText(f),f="",w[E]=(w[E]||0)+1,w[E]<=bn&&(ce+=ht),j.startsWith("_"))f+=l[0];else{let _t=K.classNameAliases[j]||j;F(l[0],_t)}}else f+=l[0];o=p.keywordPatternRe.lastIndex,l=p.keywordPatternRe.exec(y)}f+=y.substring(o),S.addText(f)}function Y(){if(y==="")return;let o=null;if(typeof p.subLanguage=="string"){if(!t[p.subLanguage]){S.addText(y);return}o=L(p.subLanguage,y,!0,ke[p.subLanguage]),ke[p.subLanguage]=o._top}else o=R(y,p.subLanguage.length?p.subLanguage:null);p.relevance>0&&(ce+=o.relevance),S.__addSublanguage(o._emitter,o.language)}function P(){p.subLanguage!=null?Y():G(),y=""}function F(o,l){o!==""&&(S.startScope(l),S.addText(o),S.endScope())}function Se(o,l){let f=1,E=l.length-1;for(;f<=E;){if(!o._emit[f]){f++;continue}let k=K.classNameAliases[o[f]]||o[f],j=l[f];k?F(j,k):(y=j,G(),y=""),f++}}function Oe(o,l){return o.scope&&typeof o.scope=="string"&&S.openNode(K.classNameAliases[o.scope]||o.scope),o.beginScope&&(o.beginScope._wrap?(F(y,K.classNameAliases[o.beginScope._wrap]||o.beginScope._wrap),y=""):o.beginScope._multi&&(Se(o.beginScope,l),y="")),p=Object.create(o,{parent:{value:p}}),p}function Re(o,l,f){let E=Mt(o.endRe,f);if(E){if(o["on:end"]){let k=new ue(o);o["on:end"](l,k),k.isMatchIgnored&&(E=!1)}if(E){for(;o.endsParent&&o.parent;)o=o.parent;return o}}if(o.endsWithParent)return Re(o.parent,l,f)}function dt(o){return p.matcher.regexIndex===0?(y+=o[0],1):(he=!0,0)}function gt(o){let l=o[0],f=o.rule,E=new ue(f),k=[f.__beforeBegin,f["on:begin"]];for(let j of k)if(j&&(j(o,E),E.isMatchIgnored))return dt(l);return f.skip?y+=l:(f.excludeBegin&&(y+=l),P(),!f.returnBegin&&!f.excludeBegin&&(y=l)),Oe(f,o),f.returnBegin?0:l.length}function pt(o){let l=o[0],f=u.substring(o.index),E=Re(p,o,f);if(!E)return Be;let k=p;p.endScope&&p.endScope._wrap?(P(),F(l,p.endScope._wrap)):p.endScope&&p.endScope._multi?(P(),Se(p.endScope,o)):k.skip?y+=l:(k.returnEnd||k.excludeEnd||(y+=l),P(),k.excludeEnd&&(y=l));do p.scope&&S.closeNode(),!p.skip&&!p.subLanguage&&(ce+=p.relevance),p=p.parent;while(p!==E.parent);return E.starts&&Oe(E.starts,o),k.returnEnd?0:l.length}function ft(){let o=[];for(let l=p;l!==K;l=l.parent)l.scope&&o.unshift(l.scope);o.forEach(l=>S.openNode(l))}let oe={};function Te(o,l){let f=l&&l[0];if(y+=o,f==null)return P(),0;if(oe.type==="begin"&&l.type==="end"&&oe.index===l.index&&f===""){if(y+=u.slice(l.index,l.index+1),!g){let E=new Error(`0 width match regex (${r})`);throw E.languageName=r,E.badRule=oe.rule,E}return 1}if(oe=l,l.type==="begin")return gt(l);if(l.type==="illegal"&&!d){let E=new Error('Illegal lexeme "'+f+'" for mode "'+(p.scope||"<unnamed>")+'"');throw E.mode=p,E}else if(l.type==="end"){let E=pt(l);if(E!==Be)return E}if(l.type==="illegal"&&f==="")return y+=`
`,1;if(be>1e5&&be>l.index*3)throw new Error("potential infinite loop, way more iterations than matches");return y+=f,f.length}let K=x(r);if(!K)throw Q(b.replace("{}",r)),new Error('Unknown language: "'+r+'"');let bt=gn(K),fe="",p=h||bt,ke={},S=new i.__emitter(i);ft();let y="",ce=0,V=0,be=0,he=!1;try{if(K.__emitTokens)K.__emitTokens(u,S);else{for(p.matcher.considerAll();;){be++,he?he=!1:p.matcher.considerAll(),p.matcher.lastIndex=V;let o=p.matcher.exec(u);if(!o)break;let l=u.substring(V,o.index),f=Te(l,o);V=o.index+f}Te(u.substring(V))}return S.finalize(),fe=S.toHTML(),{language:r,value:fe,relevance:ce,illegal:!1,_emitter:S,_top:p}}catch(o){if(o.message&&o.message.includes("Illegal"))return{language:r,value:_e(u),illegal:!0,relevance:0,_illegalBy:{message:o.message,index:V,context:u.slice(V-100,V+100),mode:o.mode,resultSoFar:fe},_emitter:S};if(g)return{language:r,value:_e(u),illegal:!1,relevance:0,errorRaised:o,_emitter:S,_top:p};throw o}}function O(r){let u={value:_e(r),illegal:!1,relevance:0,_top:a,_emitter:new i.__emitter(i)};return u._emitter.addText(r),u}function R(r,u){u=u||i.languages||Object.keys(t);let d=O(r),h=u.filter(x).filter(X).map(P=>L(P,r,!1));h.unshift(d);let w=h.sort((P,F)=>{if(P.relevance!==F.relevance)return F.relevance-P.relevance;if(P.language&&F.language){if(x(P.language).supersetOf===F.language)return 1;if(x(F.language).supersetOf===P.language)return-1}return 0}),[T,G]=w,Y=T;return Y.secondBest=G,Y}function D(r,u,d){let h=u&&n[u]||d;r.classList.add("hljs"),r.classList.add(`language-${h}`)}function A(r){let u=null,d=_(r);if(c(d))return;if(M("before:highlightElement",{el:r,language:d}),r.dataset.highlighted){console.log("Element previously highlighted. To highlight again, first unset `dataset.highlighted`.",r);return}if(r.children.length>0&&(i.ignoreUnescapedHTML||(console.warn("One of your code blocks includes unescaped HTML. This is a potentially serious security risk."),console.warn("https://github.com/highlightjs/highlight.js/wiki/security"),console.warn("The element with unescaped HTML:"),console.warn(r)),i.throwUnescapedHTML))throw new we("One of your code blocks includes unescaped HTML.",r.innerHTML);u=r;let h=u.textContent,w=d?m(h,{language:d,ignoreIllegals:!0}):R(h);r.innerHTML=w.value,r.dataset.highlighted="yes",D(r,d,w.language),r.result={language:w.language,re:w.relevance,relevance:w.relevance},w.secondBest&&(r.secondBest={language:w.secondBest.language,relevance:w.secondBest.relevance}),M("after:highlightElement",{el:r,result:w,text:h})}function B(r){i=De(i,r)}let H=()=>{U(),re("10.6.0","initHighlighting() deprecated.  Use highlightAll() now.")};function $(){U(),re("10.6.0","initHighlightingOnLoad() deprecated.  Use highlightAll() now.")}let W=!1;function U(){function r(){U()}if(document.readyState==="loading"){W||window.addEventListener("DOMContentLoaded",r,!1),W=!0;return}document.querySelectorAll(i.cssSelector).forEach(A)}function v(r,u){let d=null;try{d=u(e)}catch(h){if(Q("Language definition for '{}' could not be registered.".replace("{}",r)),g)Q(h);else throw h;d=a}d.name||(d.name=r),t[r]=d,d.rawDefinition=u.bind(null,e),d.aliases&&C(d.aliases,{languageName:r})}function N(r){delete t[r];for(let u of Object.keys(n))n[u]===r&&delete n[u]}function Z(){return Object.keys(t)}function x(r){return r=(r||"").toLowerCase(),t[r]||t[n[r]]}function C(r,{languageName:u}){typeof r=="string"&&(r=[r]),r.forEach(d=>{n[d.toLowerCase()]=u})}function X(r){let u=x(r);return u&&!u.disableAutodetect}function ee(r){r["before:highlightBlock"]&&!r["before:highlightElement"]&&(r["before:highlightElement"]=u=>{r["before:highlightBlock"](Object.assign({block:u.el},u))}),r["after:highlightBlock"]&&!r["after:highlightElement"]&&(r["after:highlightElement"]=u=>{r["after:highlightBlock"](Object.assign({block:u.el},u))})}function te(r){ee(r),s.push(r)}function ne(r){let u=s.indexOf(r);u!==-1&&s.splice(u,1)}function M(r,u){let d=r;s.forEach(function(h){h[d]&&h[d](u)})}function I(r){return re("10.7.0","highlightBlock will be removed entirely in v12.0"),re("10.7.0","Please use highlightElement now."),A(r)}Object.assign(e,{highlight:m,highlightAuto:R,highlightAll:U,highlightElement:A,highlightBlock:I,configure:B,initHighlighting:H,initHighlightingOnLoad:$,registerLanguage:v,unregisterLanguage:N,listLanguages:Z,getLanguage:x,registerAliases:C,autoDetection:X,inherit:De,addPlugin:te,removePlugin:ne}),e.debugMode=function(){g=!1},e.safeMode=function(){g=!0},e.versionString=fn,e.regex={concat:J,lookahead:ze,either:xe,optional:kt,anyNumberOfTimes:Tt};for(let r in le)typeof le[r]=="object"&&Ue(le[r]);return Object.assign(e,le),e},ie=qe({});ie.newInstance=()=>qe({});Xe.exports=ie;ie.HighlightJS=ie;ie.default=ie});var Ve=St(Ye(),1);var z=Ve.default;function Qe(e){let t=e.regex,n=/(?![A-Za-z0-9])(?![$])/,s=t.concat(/[a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*/,n),g=t.concat(/(\\?[A-Z][a-z0-9_\x7f-\xff]+|\\?[A-Z]+(?=[A-Z][a-z0-9_\x7f-\xff])){1,}/,n),b=t.concat(/[A-Z]+/,n),a={scope:"variable",match:"\\$+"+s},i={scope:"meta",variants:[{begin:/<\?php/,relevance:10},{begin:/<\?=/},{begin:/<\?/,relevance:.1},{begin:/\?>/}]},c={scope:"subst",variants:[{begin:/\$\w+/},{begin:/\{\$/,end:/\}/}]},_=e.inherit(e.APOS_STRING_MODE,{illegal:null}),m=e.inherit(e.QUOTE_STRING_MODE,{illegal:null,contains:e.QUOTE_STRING_MODE.contains.concat(c)}),L={begin:/<<<[ \t]*(?:(\w+)|"(\w+)")\n/,end:/[ \t]*(\w+)\b/,contains:e.QUOTE_STRING_MODE.contains.concat(c),"on:begin":(M,I)=>{I.data._beginMatch=M[1]||M[2]},"on:end":(M,I)=>{I.data._beginMatch!==M[1]&&I.ignoreMatch()}},O=e.END_SAME_AS_BEGIN({begin:/<<<[ \t]*'(\w+)'\n/,end:/[ \t]*(\w+)\b/}),R=`[ 	
]`,D={scope:"string",variants:[m,_,L,O]},A={scope:"number",variants:[{begin:"\\b0[bB][01]+(?:_[01]+)*\\b"},{begin:"\\b0[oO][0-7]+(?:_[0-7]+)*\\b"},{begin:"\\b0[xX][\\da-fA-F]+(?:_[\\da-fA-F]+)*\\b"},{begin:"(?:\\b\\d+(?:_\\d+)*(\\.(?:\\d+(?:_\\d+)*))?|\\B\\.\\d+)(?:[eE][+-]?\\d+)?"}],relevance:0},B=["false","null","true"],H=["__CLASS__","__DIR__","__FILE__","__FUNCTION__","__COMPILER_HALT_OFFSET__","__LINE__","__METHOD__","__NAMESPACE__","__TRAIT__","die","echo","exit","include","include_once","print","require","require_once","array","abstract","and","as","binary","bool","boolean","break","callable","case","catch","class","clone","const","continue","declare","default","do","double","else","elseif","empty","enddeclare","endfor","endforeach","endif","endswitch","endwhile","enum","eval","extends","final","finally","float","for","foreach","from","global","goto","if","implements","instanceof","insteadof","int","integer","interface","isset","iterable","list","match|0","mixed","new","never","object","or","private","protected","public","readonly","real","return","string","switch","throw","trait","try","unset","use","var","void","while","xor","yield"],$=["Error|0","AppendIterator","ArgumentCountError","ArithmeticError","ArrayIterator","ArrayObject","AssertionError","BadFunctionCallException","BadMethodCallException","CachingIterator","CallbackFilterIterator","CompileError","Countable","DirectoryIterator","DivisionByZeroError","DomainException","EmptyIterator","ErrorException","Exception","FilesystemIterator","FilterIterator","GlobIterator","InfiniteIterator","InvalidArgumentException","IteratorIterator","LengthException","LimitIterator","LogicException","MultipleIterator","NoRewindIterator","OutOfBoundsException","OutOfRangeException","OuterIterator","OverflowException","ParentIterator","ParseError","RangeException","RecursiveArrayIterator","RecursiveCachingIterator","RecursiveCallbackFilterIterator","RecursiveDirectoryIterator","RecursiveFilterIterator","RecursiveIterator","RecursiveIteratorIterator","RecursiveRegexIterator","RecursiveTreeIterator","RegexIterator","RuntimeException","SeekableIterator","SplDoublyLinkedList","SplFileInfo","SplFileObject","SplFixedArray","SplHeap","SplMaxHeap","SplMinHeap","SplObjectStorage","SplObserver","SplPriorityQueue","SplQueue","SplStack","SplSubject","SplTempFileObject","TypeError","UnderflowException","UnexpectedValueException","UnhandledMatchError","ArrayAccess","BackedEnum","Closure","Fiber","Generator","Iterator","IteratorAggregate","Serializable","Stringable","Throwable","Traversable","UnitEnum","WeakReference","WeakMap","Directory","__PHP_Incomplete_Class","parent","php_user_filter","self","static","stdClass"],U={keyword:H,literal:(M=>{let I=[];return M.forEach(r=>{I.push(r),r.toLowerCase()===r?I.push(r.toUpperCase()):I.push(r.toLowerCase())}),I})(B),built_in:$},v=M=>M.map(I=>I.replace(/\|\d+$/,"")),N={variants:[{match:[/new/,t.concat(R,"+"),t.concat("(?!",v($).join("\\b|"),"\\b)"),g],scope:{1:"keyword",4:"title.class"}}]},Z=t.concat(s,"\\b(?!\\()"),x={variants:[{match:[t.concat(/::/,t.lookahead(/(?!class\b)/)),Z],scope:{2:"variable.constant"}},{match:[/::/,/class/],scope:{2:"variable.language"}},{match:[g,t.concat(/::/,t.lookahead(/(?!class\b)/)),Z],scope:{1:"title.class",3:"variable.constant"}},{match:[g,t.concat("::",t.lookahead(/(?!class\b)/))],scope:{1:"title.class"}},{match:[g,/::/,/class/],scope:{1:"title.class",3:"variable.language"}}]},C={scope:"attr",match:t.concat(s,t.lookahead(":"),t.lookahead(/(?!::)/))},X={relevance:0,begin:/\(/,end:/\)/,keywords:U,contains:[C,a,x,e.C_BLOCK_COMMENT_MODE,D,A,N]},ee={relevance:0,match:[/\b/,t.concat("(?!fn\\b|function\\b|",v(H).join("\\b|"),"|",v($).join("\\b|"),"\\b)"),s,t.concat(R,"*"),t.lookahead(/(?=\()/)],scope:{3:"title.function.invoke"},contains:[X]};X.contains.push(ee);let te=[C,x,e.C_BLOCK_COMMENT_MODE,D,A,N],ne={begin:t.concat(/#\[\s*\\?/,t.either(g,b)),beginScope:"meta",end:/]/,endScope:"meta",keywords:{literal:B,keyword:["new","array"]},contains:[{begin:/\[/,end:/]/,keywords:{literal:B,keyword:["new","array"]},contains:["self",...te]},...te,{scope:"meta",variants:[{match:g},{match:b}]}]};return{case_insensitive:!1,keywords:U,contains:[ne,e.HASH_COMMENT_MODE,e.COMMENT("//","$"),e.COMMENT("/\\*","\\*/",{contains:[{scope:"doctag",match:"@[A-Za-z]+"}]}),{match:/__halt_compiler\(\);/,keywords:"__halt_compiler",starts:{scope:"comment",end:e.MATCH_NOTHING_RE,contains:[{match:/\?>/,scope:"meta",endsParent:!0}]}},i,{scope:"variable.language",match:/\$this\b/},a,ee,x,{match:[/const/,/\s/,s],scope:{1:"keyword",3:"variable.constant"}},N,{scope:"function",relevance:0,beginKeywords:"fn function",end:/[;{]/,excludeEnd:!0,illegal:"[$%\\[]",contains:[{beginKeywords:"use"},e.UNDERSCORE_TITLE_MODE,{begin:"=>",endsParent:!0},{scope:"params",begin:"\\(",end:"\\)",excludeBegin:!0,excludeEnd:!0,keywords:U,contains:["self",ne,a,x,e.C_BLOCK_COMMENT_MODE,D,A]}]},{scope:"class",variants:[{beginKeywords:"enum",illegal:/[($"]/},{beginKeywords:"class interface trait",illegal:/[:($"]/}],relevance:0,end:/\{/,excludeEnd:!0,contains:[{beginKeywords:"extends implements"},e.UNDERSCORE_TITLE_MODE]},{beginKeywords:"namespace",relevance:0,end:";",illegal:/[.']/,contains:[e.inherit(e.UNDERSCORE_TITLE_MODE,{scope:"title.class"})]},{beginKeywords:"use",relevance:0,end:";",contains:[{match:/\b(as|const|function)\b/,scope:"keyword"},e.UNDERSCORE_TITLE_MODE]},D,A]}}function Je(e){return{name:"PHP template",subLanguage:"xml",contains:[{begin:/<\?(php|=)?/,end:/\?>/,subLanguage:"php",contains:[{begin:"/\\*",end:"\\*/",skip:!0},{begin:'b"',end:'"',skip:!0},{begin:"b'",end:"'",skip:!0},e.inherit(e.APOS_STRING_MODE,{illegal:null,className:null,contains:null,skip:!0}),e.inherit(e.QUOTE_STRING_MODE,{illegal:null,className:null,contains:null,skip:!0})]}]}}var et="[A-Za-z$_][0-9A-Za-z$_]*",hn=["as","in","of","if","for","while","finally","var","new","function","do","return","void","else","break","catch","instanceof","with","throw","case","default","try","switch","continue","typeof","delete","let","yield","const","class","debugger","async","await","static","import","from","export","extends","using"],_n=["true","false","null","undefined","NaN","Infinity"],tt=["Object","Function","Boolean","Symbol","Math","Date","Number","BigInt","String","RegExp","Array","Float32Array","Float64Array","Int8Array","Uint8Array","Uint8ClampedArray","Int16Array","Int32Array","Uint16Array","Uint32Array","BigInt64Array","BigUint64Array","Set","Map","WeakSet","WeakMap","ArrayBuffer","SharedArrayBuffer","Atomics","DataView","JSON","Promise","Generator","GeneratorFunction","AsyncFunction","Reflect","Proxy","Intl","WebAssembly"],nt=["Error","EvalError","InternalError","RangeError","ReferenceError","SyntaxError","TypeError","URIError"],rt=["setInterval","setTimeout","clearInterval","clearTimeout","require","exports","eval","isFinite","isNaN","parseFloat","parseInt","decodeURI","decodeURIComponent","encodeURI","encodeURIComponent","escape","unescape"],mn=["arguments","this","super","console","window","document","localStorage","sessionStorage","module","global"],En=[].concat(rt,tt,nt);function it(e){let t=e.regex,n=(d,{after:h})=>{let w="</"+d[0].slice(1);return d.input.indexOf(w,h)!==-1},s=et,g={begin:"<>",end:"</>"},b=/<[A-Za-z0-9\\._:-]+\s*\/>/,a={begin:/<[A-Za-z0-9\\._:-]+/,end:/\/[A-Za-z0-9\\._:-]+>|\/>/,isTrulyOpeningTag:(d,h)=>{let w=d[0].length+d.index,T=d.input[w];if(T==="<"||T===","){h.ignoreMatch();return}T===">"&&(n(d,{after:w})||h.ignoreMatch());let G,Y=d.input.substring(w);if(G=Y.match(/^\s*=/)){h.ignoreMatch();return}if((G=Y.match(/^\s+extends\s+/))&&G.index===0){h.ignoreMatch();return}}},i={$pattern:et,keyword:hn,literal:_n,built_in:En,"variable.language":mn},c="[0-9](_?[0-9])*",_=`\\.(${c})`,m="0|[1-9](_?[0-9])*|0[0-7]*[89][0-9]*",L={className:"number",variants:[{begin:`(\\b(${m})((${_})|\\.)?|(${_}))[eE][+-]?(${c})\\b`},{begin:`\\b(${m})\\b((${_})\\b|\\.)?|(${_})\\b`},{begin:"\\b(0|[1-9](_?[0-9])*)n\\b"},{begin:"\\b0[xX][0-9a-fA-F](_?[0-9a-fA-F])*n?\\b"},{begin:"\\b0[bB][0-1](_?[0-1])*n?\\b"},{begin:"\\b0[oO][0-7](_?[0-7])*n?\\b"},{begin:"\\b0[0-7]+n?\\b"}],relevance:0},O={className:"subst",begin:"\\$\\{",end:"\\}",keywords:i,contains:[]},R={begin:".?html`",end:"",starts:{end:"`",returnEnd:!1,contains:[e.BACKSLASH_ESCAPE,O],subLanguage:"xml"}},D={begin:".?css`",end:"",starts:{end:"`",returnEnd:!1,contains:[e.BACKSLASH_ESCAPE,O],subLanguage:"css"}},A={begin:".?gql`",end:"",starts:{end:"`",returnEnd:!1,contains:[e.BACKSLASH_ESCAPE,O],subLanguage:"graphql"}},B={className:"string",begin:"`",end:"`",contains:[e.BACKSLASH_ESCAPE,O]},$={className:"comment",variants:[e.COMMENT(/\/\*\*(?!\/)/,"\\*/",{relevance:0,contains:[{begin:"(?=@[A-Za-z]+)",relevance:0,contains:[{className:"doctag",begin:"@[A-Za-z]+"},{className:"type",begin:"\\{",end:"\\}",excludeEnd:!0,excludeBegin:!0,relevance:0},{className:"variable",begin:s+"(?=\\s*(-)|$)",endsParent:!0,relevance:0},{begin:/(?=[^\n])\s/,relevance:0}]}]}),e.C_BLOCK_COMMENT_MODE,e.C_LINE_COMMENT_MODE]},W=[e.APOS_STRING_MODE,e.QUOTE_STRING_MODE,R,D,A,B,{match:/\$\d+/},L];O.contains=W.concat({begin:/\{/,end:/\}/,keywords:i,contains:["self"].concat(W)});let U=[].concat($,O.contains),v=U.concat([{begin:/(\s*)\(/,end:/\)/,keywords:i,contains:["self"].concat(U)}]),N={className:"params",begin:/(\s*)\(/,end:/\)/,excludeBegin:!0,excludeEnd:!0,keywords:i,contains:v},Z={variants:[{match:[/class/,/\s+/,s,/\s+/,/extends/,/\s+/,t.concat(s,"(",t.concat(/\./,s),")*")],scope:{1:"keyword",3:"title.class",5:"keyword",7:"title.class.inherited"}},{match:[/class/,/\s+/,s],scope:{1:"keyword",3:"title.class"}}]},x={relevance:0,match:t.either(/\bJSON/,/\b[A-Z][a-z]+([A-Z][a-z]*|\d)*/,/\b[A-Z]{2,}([A-Z][a-z]+|\d)+([A-Z][a-z]*)*/,/\b[A-Z]{2,}[a-z]+([A-Z][a-z]+|\d)*([A-Z][a-z]*)*/),className:"title.class",keywords:{_:[...tt,...nt]}},C={label:"use_strict",className:"meta",relevance:10,begin:/^\s*['"]use (strict|asm)['"]/},X={variants:[{match:[/function/,/\s+/,s,/(?=\s*\()/]},{match:[/function/,/\s*(?=\()/]}],className:{1:"keyword",3:"title.function"},label:"func.def",contains:[N],illegal:/%/},ee={relevance:0,match:/\b[A-Z][A-Z_0-9]+\b/,className:"variable.constant"};function te(d){return t.concat("(?!",d.join("|"),")")}let ne={match:t.concat(/\b/,te([...rt,"super","import"].map(d=>`${d}\\s*\\(`)),s,t.lookahead(/\s*\(/)),className:"title.function",relevance:0},M={begin:t.concat(/\./,t.lookahead(t.concat(s,/(?![0-9A-Za-z$_(])/))),end:s,excludeBegin:!0,keywords:"prototype",className:"property",relevance:0},I={match:[/get|set/,/\s+/,s,/(?=\()/],className:{1:"keyword",3:"title.function"},contains:[{begin:/\(\)/},N]},r="(\\([^()]*(\\([^()]*(\\([^()]*\\)[^()]*)*\\)[^()]*)*\\)|"+e.UNDERSCORE_IDENT_RE+")\\s*=>",u={match:[/const|var|let/,/\s+/,s,/\s*/,/=\s*/,/(async\s*)?/,t.lookahead(r)],keywords:"async",className:{1:"keyword",3:"title.function"},contains:[N]};return{name:"JavaScript",aliases:["js","jsx","mjs","cjs"],keywords:i,exports:{PARAMS_CONTAINS:v,CLASS_REFERENCE:x},illegal:/#(?![$_A-z])/,contains:[e.SHEBANG({label:"shebang",binary:"node",relevance:5}),C,e.APOS_STRING_MODE,e.QUOTE_STRING_MODE,R,D,A,B,$,{match:/\$\d+/},L,x,{scope:"attr",match:s+t.lookahead(":"),relevance:0},u,{begin:"("+e.RE_STARTERS_RE+"|\\b(case|return|throw)\\b)\\s*",keywords:"return throw case",relevance:0,contains:[$,e.REGEXP_MODE,{className:"function",begin:r,returnBegin:!0,end:"\\s*=>",contains:[{className:"params",variants:[{begin:e.UNDERSCORE_IDENT_RE,relevance:0},{className:null,begin:/\(\s*\)/,skip:!0},{begin:/(\s*)\(/,end:/\)/,excludeBegin:!0,excludeEnd:!0,keywords:i,contains:v}]}]},{begin:/,/,relevance:0},{match:/\s+/,relevance:0},{variants:[{begin:g.begin,end:g.end},{match:b},{begin:a.begin,"on:begin":a.isTrulyOpeningTag,end:a.end}],subLanguage:"xml",contains:[{begin:a.begin,end:a.end,skip:!0,contains:["self"]}]}]},X,{beginKeywords:"while if switch catch for"},{begin:"\\b(?!function)"+e.UNDERSCORE_IDENT_RE+"\\([^()]*(\\([^()]*(\\([^()]*\\)[^()]*)*\\)[^()]*)*\\)\\s*\\{",returnBegin:!0,label:"func.def",contains:[N,e.inherit(e.TITLE_MODE,{begin:s,className:"title.function"})]},{match:/\.\.\./,relevance:0},M,{match:"\\$"+s,relevance:0},{match:[/\bconstructor(?=\s*\()/],className:{1:"title.function"},contains:[N]},ne,ee,Z,I,{match:/\$[(.]/}]}}function at(e){let t=e.regex,n=e.COMMENT("--","$"),s={scope:"string",variants:[{begin:/'/,end:/'/,contains:[{match:/''/}]}]},g={begin:/"/,end:/"/,contains:[{match:/""/}]},b=["true","false","unknown"],a=["double precision","large object","with timezone","without timezone"],i=["bigint","binary","blob","boolean","char","character","clob","date","dec","decfloat","decimal","float","int","integer","interval","nchar","nclob","national","numeric","real","row","smallint","time","timestamp","varchar","varying","varbinary"],c=["add","asc","collation","desc","final","first","last","view"],_=["abs","acos","all","allocate","alter","and","any","are","array","array_agg","array_max_cardinality","as","asensitive","asin","asymmetric","at","atan","atomic","authorization","avg","begin","begin_frame","begin_partition","between","bigint","binary","blob","boolean","both","by","call","called","cardinality","cascaded","case","cast","ceil","ceiling","char","char_length","character","character_length","check","classifier","clob","close","coalesce","collate","collect","column","commit","condition","connect","constraint","contains","convert","copy","corr","corresponding","cos","cosh","count","covar_pop","covar_samp","create","cross","cube","cume_dist","current","current_catalog","current_date","current_default_transform_group","current_path","current_role","current_row","current_schema","current_time","current_timestamp","current_path","current_role","current_transform_group_for_type","current_user","cursor","cycle","date","day","deallocate","dec","decimal","decfloat","declare","default","define","delete","dense_rank","deref","describe","deterministic","disconnect","distinct","double","drop","dynamic","each","element","else","empty","end","end_frame","end_partition","end-exec","equals","escape","every","except","exec","execute","exists","exp","external","extract","false","fetch","filter","first_value","float","floor","for","foreign","frame_row","free","from","full","function","fusion","get","global","grant","group","grouping","groups","having","hold","hour","identity","in","indicator","initial","inner","inout","insensitive","insert","int","integer","intersect","intersection","interval","into","is","join","json_array","json_arrayagg","json_exists","json_object","json_objectagg","json_query","json_table","json_table_primitive","json_value","lag","language","large","last_value","lateral","lead","leading","left","like","like_regex","listagg","ln","local","localtime","localtimestamp","log","log10","lower","match","match_number","match_recognize","matches","max","member","merge","method","min","minute","mod","modifies","module","month","multiset","national","natural","nchar","nclob","new","no","none","normalize","not","nth_value","ntile","null","nullif","numeric","octet_length","occurrences_regex","of","offset","old","omit","on","one","only","open","or","order","out","outer","over","overlaps","overlay","parameter","partition","pattern","per","percent","percent_rank","percentile_cont","percentile_disc","period","portion","position","position_regex","power","precedes","precision","prepare","primary","procedure","ptf","range","rank","reads","real","recursive","ref","references","referencing","regr_avgx","regr_avgy","regr_count","regr_intercept","regr_r2","regr_slope","regr_sxx","regr_sxy","regr_syy","release","result","return","returns","revoke","right","rollback","rollup","row","row_number","rows","running","savepoint","scope","scroll","search","second","seek","select","sensitive","session_user","set","show","similar","sin","sinh","skip","smallint","some","specific","specifictype","sql","sqlexception","sqlstate","sqlwarning","sqrt","start","static","stddev_pop","stddev_samp","submultiset","subset","substring","substring_regex","succeeds","sum","symmetric","system","system_time","system_user","table","tablesample","tan","tanh","then","time","timestamp","timezone_hour","timezone_minute","to","trailing","translate","translate_regex","translation","treat","trigger","trim","trim_array","true","truncate","uescape","union","unique","unknown","unnest","update","upper","user","using","value","values","value_of","var_pop","var_samp","varbinary","varchar","varying","versioning","when","whenever","where","width_bucket","window","with","within","without","year"],m=["abs","acos","array_agg","asin","atan","avg","cast","ceil","ceiling","coalesce","corr","cos","cosh","count","covar_pop","covar_samp","cume_dist","dense_rank","deref","element","exp","extract","first_value","floor","json_array","json_arrayagg","json_exists","json_object","json_objectagg","json_query","json_table","json_table_primitive","json_value","lag","last_value","lead","listagg","ln","log","log10","lower","max","min","mod","nth_value","ntile","nullif","percent_rank","percentile_cont","percentile_disc","position","position_regex","power","rank","regr_avgx","regr_avgy","regr_count","regr_intercept","regr_r2","regr_slope","regr_sxx","regr_sxy","regr_syy","row_number","sin","sinh","sqrt","stddev_pop","stddev_samp","substring","substring_regex","sum","tan","tanh","translate","translate_regex","treat","trim","trim_array","unnest","upper","value_of","var_pop","var_samp","width_bucket"],L=["current_catalog","current_date","current_default_transform_group","current_path","current_role","current_schema","current_transform_group_for_type","current_user","session_user","system_time","system_user","current_time","localtime","current_timestamp","localtimestamp"],O=["create table","insert into","primary key","foreign key","not null","alter table","add constraint","grouping sets","on overflow","character set","respect nulls","ignore nulls","nulls first","nulls last","depth first","breadth first"],R=m,D=[..._,...c].filter(v=>!m.includes(v)),A={scope:"variable",match:/@[a-z0-9][a-z0-9_]*/},B={scope:"operator",match:/[-+*/=%^~]|&&?|\|\|?|!=?|<(?:=>?|<|>)?|>[>=]?/,relevance:0},H={match:t.concat(/\b/,t.either(...R),/\s*\(/),relevance:0,keywords:{built_in:R}};function $(v){return t.concat(/\b/,t.either(...v.map(N=>N.replace(/\s+/,"\\s+"))),/\b/)}let W={scope:"keyword",match:$(O),relevance:0};function U(v,{exceptions:N,when:Z}={}){let x=Z;return N=N||[],v.map(C=>C.match(/\|\d+$/)||N.includes(C)?C:x(C)?`${C}|0`:C)}return{name:"SQL",case_insensitive:!0,illegal:/[{}]|<\//,keywords:{$pattern:/\b[\w\.]+/,keyword:U(D,{when:v=>v.length<3}),literal:b,type:i,built_in:L},contains:[{scope:"type",match:$(a)},W,H,A,s,g,e.C_NUMBER_MODE,e.C_BLOCK_COMMENT_MODE,n,B]}}function st(e){return{name:"Shell Session",aliases:["console","shellsession"],contains:[{className:"meta.prompt",begin:/^\s{0,3}[/~\w\d[\]()@-]*[>%$#][ ]?/,starts:{end:/[^\\](?=\s*$)/,subLanguage:"bash"}}]}}var yn=e=>({IMPORTANT:{scope:"meta",begin:"!important"},BLOCK_COMMENT:e.C_BLOCK_COMMENT_MODE,HEXCOLOR:{scope:"number",begin:/#(([0-9a-fA-F]{3,4})|(([0-9a-fA-F]{2}){3,4}))\b/},FUNCTION_DISPATCH:{className:"built_in",begin:/[\w-]+(?=\()/},ATTRIBUTE_SELECTOR_MODE:{scope:"selector-attr",begin:/\[/,end:/\]/,illegal:"$",contains:[e.APOS_STRING_MODE,e.QUOTE_STRING_MODE]},CSS_NUMBER_MODE:{scope:"number",begin:e.NUMBER_RE+"(%|em|ex|ch|rem|vw|vh|vmin|vmax|cm|mm|in|pt|pc|px|deg|grad|rad|turn|s|ms|Hz|kHz|dpi|dpcm|dppx)?",relevance:0},CSS_VARIABLE:{className:"attr",begin:/--[A-Za-z_][A-Za-z0-9_-]*/}}),wn=["a","abbr","address","article","aside","audio","b","blockquote","body","button","canvas","caption","cite","code","dd","del","details","dfn","div","dl","dt","em","fieldset","figcaption","figure","footer","form","h1","h2","h3","h4","h5","h6","header","hgroup","html","i","iframe","img","input","ins","kbd","label","legend","li","main","mark","menu","nav","object","ol","optgroup","option","p","picture","q","quote","samp","section","select","source","span","strong","summary","sup","table","tbody","td","textarea","tfoot","th","thead","time","tr","ul","var","video"],xn=["defs","g","marker","mask","pattern","svg","switch","symbol","feBlend","feColorMatrix","feComponentTransfer","feComposite","feConvolveMatrix","feDiffuseLighting","feDisplacementMap","feFlood","feGaussianBlur","feImage","feMerge","feMorphology","feOffset","feSpecularLighting","feTile","feTurbulence","linearGradient","radialGradient","stop","circle","ellipse","image","line","path","polygon","polyline","rect","text","use","textPath","tspan","foreignObject","clipPath"],vn=[...wn,...xn],Nn=["any-hover","any-pointer","aspect-ratio","color","color-gamut","color-index","device-aspect-ratio","device-height","device-width","display-mode","forced-colors","grid","height","hover","inverted-colors","monochrome","orientation","overflow-block","overflow-inline","pointer","prefers-color-scheme","prefers-contrast","prefers-reduced-motion","prefers-reduced-transparency","resolution","scan","scripting","update","width","min-width","max-width","min-height","max-height"].sort().reverse(),Sn=["active","any-link","blank","checked","current","default","defined","dir","disabled","drop","empty","enabled","first","first-child","first-of-type","fullscreen","future","focus","focus-visible","focus-within","has","host","host-context","hover","indeterminate","in-range","invalid","is","lang","last-child","last-of-type","left","link","local-link","not","nth-child","nth-col","nth-last-child","nth-last-col","nth-last-of-type","nth-of-type","only-child","only-of-type","optional","out-of-range","past","placeholder-shown","read-only","read-write","required","right","root","scope","target","target-within","user-invalid","valid","visited","where"].sort().reverse(),On=["after","backdrop","before","cue","cue-region","first-letter","first-line","grammar-error","marker","part","placeholder","selection","slotted","spelling-error"].sort().reverse(),Rn=["accent-color","align-content","align-items","align-self","alignment-baseline","all","anchor-name","animation","animation-composition","animation-delay","animation-direction","animation-duration","animation-fill-mode","animation-iteration-count","animation-name","animation-play-state","animation-range","animation-range-end","animation-range-start","animation-timeline","animation-timing-function","appearance","aspect-ratio","backdrop-filter","backface-visibility","background","background-attachment","background-blend-mode","background-clip","background-color","background-image","background-origin","background-position","background-position-x","background-position-y","background-repeat","background-size","baseline-shift","block-size","border","border-block","border-block-color","border-block-end","border-block-end-color","border-block-end-style","border-block-end-width","border-block-start","border-block-start-color","border-block-start-style","border-block-start-width","border-block-style","border-block-width","border-bottom","border-bottom-color","border-bottom-left-radius","border-bottom-right-radius","border-bottom-style","border-bottom-width","border-collapse","border-color","border-end-end-radius","border-end-start-radius","border-image","border-image-outset","border-image-repeat","border-image-slice","border-image-source","border-image-width","border-inline","border-inline-color","border-inline-end","border-inline-end-color","border-inline-end-style","border-inline-end-width","border-inline-start","border-inline-start-color","border-inline-start-style","border-inline-start-width","border-inline-style","border-inline-width","border-left","border-left-color","border-left-style","border-left-width","border-radius","border-right","border-right-color","border-right-style","border-right-width","border-spacing","border-start-end-radius","border-start-start-radius","border-style","border-top","border-top-color","border-top-left-radius","border-top-right-radius","border-top-style","border-top-width","border-width","bottom","box-align","box-decoration-break","box-direction","box-flex","box-flex-group","box-lines","box-ordinal-group","box-orient","box-pack","box-shadow","box-sizing","break-after","break-before","break-inside","caption-side","caret-color","clear","clip","clip-path","clip-rule","color","color-interpolation","color-interpolation-filters","color-profile","color-rendering","color-scheme","column-count","column-fill","column-gap","column-rule","column-rule-color","column-rule-style","column-rule-width","column-span","column-width","columns","contain","contain-intrinsic-block-size","contain-intrinsic-height","contain-intrinsic-inline-size","contain-intrinsic-size","contain-intrinsic-width","container","container-name","container-type","content","content-visibility","counter-increment","counter-reset","counter-set","cue","cue-after","cue-before","cursor","cx","cy","direction","display","dominant-baseline","empty-cells","enable-background","field-sizing","fill","fill-opacity","fill-rule","filter","flex","flex-basis","flex-direction","flex-flow","flex-grow","flex-shrink","flex-wrap","float","flood-color","flood-opacity","flow","font","font-display","font-family","font-feature-settings","font-kerning","font-language-override","font-optical-sizing","font-palette","font-size","font-size-adjust","font-smooth","font-smoothing","font-stretch","font-style","font-synthesis","font-synthesis-position","font-synthesis-small-caps","font-synthesis-style","font-synthesis-weight","font-variant","font-variant-alternates","font-variant-caps","font-variant-east-asian","font-variant-emoji","font-variant-ligatures","font-variant-numeric","font-variant-position","font-variation-settings","font-weight","forced-color-adjust","gap","glyph-orientation-horizontal","glyph-orientation-vertical","grid","grid-area","grid-auto-columns","grid-auto-flow","grid-auto-rows","grid-column","grid-column-end","grid-column-start","grid-gap","grid-row","grid-row-end","grid-row-start","grid-template","grid-template-areas","grid-template-columns","grid-template-rows","hanging-punctuation","height","hyphenate-character","hyphenate-limit-chars","hyphens","icon","image-orientation","image-rendering","image-resolution","ime-mode","initial-letter","initial-letter-align","inline-size","inset","inset-area","inset-block","inset-block-end","inset-block-start","inset-inline","inset-inline-end","inset-inline-start","isolation","justify-content","justify-items","justify-self","kerning","left","letter-spacing","lighting-color","line-break","line-height","line-height-step","list-style","list-style-image","list-style-position","list-style-type","margin","margin-block","margin-block-end","margin-block-start","margin-bottom","margin-inline","margin-inline-end","margin-inline-start","margin-left","margin-right","margin-top","margin-trim","marker","marker-end","marker-mid","marker-start","marks","mask","mask-border","mask-border-mode","mask-border-outset","mask-border-repeat","mask-border-slice","mask-border-source","mask-border-width","mask-clip","mask-composite","mask-image","mask-mode","mask-origin","mask-position","mask-repeat","mask-size","mask-type","masonry-auto-flow","math-depth","math-shift","math-style","max-block-size","max-height","max-inline-size","max-width","min-block-size","min-height","min-inline-size","min-width","mix-blend-mode","nav-down","nav-index","nav-left","nav-right","nav-up","none","normal","object-fit","object-position","offset","offset-anchor","offset-distance","offset-path","offset-position","offset-rotate","opacity","order","orphans","outline","outline-color","outline-offset","outline-style","outline-width","overflow","overflow-anchor","overflow-block","overflow-clip-margin","overflow-inline","overflow-wrap","overflow-x","overflow-y","overlay","overscroll-behavior","overscroll-behavior-block","overscroll-behavior-inline","overscroll-behavior-x","overscroll-behavior-y","padding","padding-block","padding-block-end","padding-block-start","padding-bottom","padding-inline","padding-inline-end","padding-inline-start","padding-left","padding-right","padding-top","page","page-break-after","page-break-before","page-break-inside","paint-order","pause","pause-after","pause-before","perspective","perspective-origin","place-content","place-items","place-self","pointer-events","position","position-anchor","position-visibility","print-color-adjust","quotes","r","resize","rest","rest-after","rest-before","right","rotate","row-gap","ruby-align","ruby-position","scale","scroll-behavior","scroll-margin","scroll-margin-block","scroll-margin-block-end","scroll-margin-block-start","scroll-margin-bottom","scroll-margin-inline","scroll-margin-inline-end","scroll-margin-inline-start","scroll-margin-left","scroll-margin-right","scroll-margin-top","scroll-padding","scroll-padding-block","scroll-padding-block-end","scroll-padding-block-start","scroll-padding-bottom","scroll-padding-inline","scroll-padding-inline-end","scroll-padding-inline-start","scroll-padding-left","scroll-padding-right","scroll-padding-top","scroll-snap-align","scroll-snap-stop","scroll-snap-type","scroll-timeline","scroll-timeline-axis","scroll-timeline-name","scrollbar-color","scrollbar-gutter","scrollbar-width","shape-image-threshold","shape-margin","shape-outside","shape-rendering","speak","speak-as","src","stop-color","stop-opacity","stroke","stroke-dasharray","stroke-dashoffset","stroke-linecap","stroke-linejoin","stroke-miterlimit","stroke-opacity","stroke-width","tab-size","table-layout","text-align","text-align-all","text-align-last","text-anchor","text-combine-upright","text-decoration","text-decoration-color","text-decoration-line","text-decoration-skip","text-decoration-skip-ink","text-decoration-style","text-decoration-thickness","text-emphasis","text-emphasis-color","text-emphasis-position","text-emphasis-style","text-indent","text-justify","text-orientation","text-overflow","text-rendering","text-shadow","text-size-adjust","text-transform","text-underline-offset","text-underline-position","text-wrap","text-wrap-mode","text-wrap-style","timeline-scope","top","touch-action","transform","transform-box","transform-origin","transform-style","transition","transition-behavior","transition-delay","transition-duration","transition-property","transition-timing-function","translate","unicode-bidi","user-modify","user-select","vector-effect","vertical-align","view-timeline","view-timeline-axis","view-timeline-inset","view-timeline-name","view-transition-name","visibility","voice-balance","voice-duration","voice-family","voice-pitch","voice-range","voice-rate","voice-stress","voice-volume","white-space","white-space-collapse","widows","width","will-change","word-break","word-spacing","word-wrap","writing-mode","x","y","z-index","zoom"].sort().reverse();function ot(e){let t=e.regex,n=yn(e),s={begin:/-(webkit|moz|ms|o)-(?=[a-z])/},g="and or not only",b=/@-?\w[\w]*(-\w+)*/,a="[a-zA-Z-][a-zA-Z0-9_-]*",i=[e.APOS_STRING_MODE,e.QUOTE_STRING_MODE];return{name:"CSS",case_insensitive:!0,illegal:/[=|'\$]/,keywords:{keyframePosition:"from to"},classNameAliases:{keyframePosition:"selector-tag"},contains:[n.BLOCK_COMMENT,s,n.CSS_NUMBER_MODE,{className:"selector-id",begin:/#[A-Za-z0-9_-]+/,relevance:0},{className:"selector-class",begin:"\\."+a,relevance:0},n.ATTRIBUTE_SELECTOR_MODE,{className:"selector-pseudo",variants:[{begin:":("+Sn.join("|")+")"},{begin:":(:)?("+On.join("|")+")"}]},n.CSS_VARIABLE,{className:"attribute",begin:"\\b("+Rn.join("|")+")\\b"},{begin:/:/,end:/[;}{]/,contains:[n.BLOCK_COMMENT,n.HEXCOLOR,n.IMPORTANT,n.CSS_NUMBER_MODE,...i,{begin:/(url|data-uri)\(/,end:/\)/,relevance:0,keywords:{built_in:"url data-uri"},contains:[...i,{className:"string",begin:/[^)]/,endsWithParent:!0,excludeEnd:!0}]},n.FUNCTION_DISPATCH]},{begin:t.lookahead(/@/),end:"[{;]",relevance:0,illegal:/:/,contains:[{className:"keyword",begin:b},{begin:/\s/,endsWithParent:!0,excludeEnd:!0,relevance:0,keywords:{$pattern:/[a-z-]+/,keyword:g,attribute:Nn.join(" ")},contains:[{begin:/[a-z-]+(?=:)/,className:"attribute"},...i,n.CSS_NUMBER_MODE]}]},{className:"selector-tag",begin:"\\b("+vn.join("|")+")\\b"}]}}function ct(e){return{name:"Plain text",aliases:["text","txt"],disableAutodetect:!0}}function lt(e){let t=e.regex,n=t.concat(/[\p{L}_]/u,t.optional(/[\p{L}0-9_.-]*:/u),/[\p{L}0-9_.-]*/u),s=/[\p{L}0-9._:-]+/u,g={className:"symbol",begin:/&[a-z]+;|&#[0-9]+;|&#x[a-f0-9]+;/},b={begin:/\s/,contains:[{className:"keyword",begin:/#?[a-z_][a-z1-9_-]+/,illegal:/\n/}]},a=e.inherit(b,{begin:/\(/,end:/\)/}),i=e.inherit(e.APOS_STRING_MODE,{className:"string"}),c=e.inherit(e.QUOTE_STRING_MODE,{className:"string"}),_={endsWithParent:!0,illegal:/</,relevance:0,contains:[{className:"attr",begin:s,relevance:0},{begin:/=\s*/,relevance:0,contains:[{className:"string",endsParent:!0,variants:[{begin:/"/,end:/"/,contains:[g]},{begin:/'/,end:/'/,contains:[g]},{begin:/[^\s"'=<>`]+/}]}]}]};return{name:"HTML, XML",aliases:["html","xhtml","rss","atom","xjb","xsd","xsl","plist","wsf","svg"],case_insensitive:!0,unicodeRegex:!0,contains:[{className:"meta",begin:/<![a-z]/,end:/>/,relevance:10,contains:[b,c,i,a,{begin:/\[/,end:/\]/,contains:[{className:"meta",begin:/<![a-z]/,end:/>/,contains:[b,a,c,i]}]}]},e.COMMENT(/<!--/,/-->/,{relevance:10}),{begin:/<!\[CDATA\[/,end:/\]\]>/,relevance:10},g,{className:"meta",end:/\?>/,variants:[{begin:/<\?xml/,relevance:10,contains:[c]},{begin:/<\?[a-z][a-z0-9]+/}]},{className:"tag",begin:/<style(?=\s|>)/,end:/>/,keywords:{name:"style"},contains:[_],starts:{end:/<\/style>/,returnEnd:!0,subLanguage:["css","xml"]}},{className:"tag",begin:/<script(?=\s|>)/,end:/>/,keywords:{name:"script"},contains:[_],starts:{end:/<\/script>/,returnEnd:!0,subLanguage:["javascript","handlebars","xml"]}},{className:"tag",begin:/<>|<\/>/},{className:"tag",begin:t.concat(/</,t.lookahead(t.concat(n,t.either(/\/>/,/>/,/\s/)))),end:/\/?>/,contains:[{className:"name",begin:n,relevance:0,starts:_}]},{className:"tag",begin:t.concat(/<\//,t.lookahead(t.concat(n,/>/))),contains:[{className:"name",begin:n,relevance:0},{begin:/>/,relevance:0,endsParent:!0}]}]}}function ut(e){let t="true false yes no null",n="[\\w#;/?:@&=+$,.~*'()[\\]]+",s={className:"attr",variants:[{begin:/[\w*@][\w*@ :()\./-]*:(?=[ \t]|$)/},{begin:/"[\w*@][\w*@ :()\./-]*":(?=[ \t]|$)/},{begin:/'[\w*@][\w*@ :()\./-]*':(?=[ \t]|$)/}]},g={className:"template-variable",variants:[{begin:/\{\{/,end:/\}\}/},{begin:/%\{/,end:/\}/}]},b={className:"string",relevance:0,begin:/'/,end:/'/,contains:[{match:/''/,scope:"char.escape",relevance:0}]},a={className:"string",relevance:0,variants:[{begin:/"/,end:/"/},{begin:/\S+/}],contains:[e.BACKSLASH_ESCAPE,g]},i=e.inherit(a,{variants:[{begin:/'/,end:/'/,contains:[{begin:/''/,relevance:0}]},{begin:/"/,end:/"/},{begin:/[^\s,{}[\]]+/}]}),O={className:"number",begin:"\\b"+"[0-9]{4}(-[0-9][0-9]){0,2}"+"([Tt \\t][0-9][0-9]?(:[0-9][0-9]){2})?"+"(\\.[0-9]*)?"+"([ \\t])*(Z|[-+][0-9][0-9]?(:[0-9][0-9])?)?"+"\\b"},R={end:",",endsWithParent:!0,excludeEnd:!0,keywords:t,relevance:0},D={begin:/\{/,end:/\}/,contains:[R],illegal:"\\n",relevance:0},A={begin:"\\[",end:"\\]",contains:[R],illegal:"\\n",relevance:0},B=[s,{className:"meta",begin:"^---\\s*$",relevance:10},{className:"string",begin:"[\\|>]([1-9]?[+-])?[ ]*\\n( +)[^ ][^\\n]*\\n(\\2[^\\n]+\\n?)*"},{begin:"<%[%=-]?",end:"[%-]?%>",subLanguage:"ruby",excludeBegin:!0,excludeEnd:!0,relevance:0},{className:"type",begin:"!\\w+!"+n},{className:"type",begin:"!<"+n+">"},{className:"type",begin:"!"+n},{className:"type",begin:"!!"+n},{className:"meta",begin:"&"+e.UNDERSCORE_IDENT_RE+"$"},{className:"meta",begin:"\\*"+e.UNDERSCORE_IDENT_RE+"$"},{className:"bullet",begin:"-(?=[ ]|$)",relevance:0},e.HASH_COMMENT_MODE,{beginKeywords:t,keywords:{literal:t}},O,{className:"number",begin:e.C_NUMBER_RE+"\\b",relevance:0},D,A,b,a],H=[...B];return H.pop(),H.push(i),R.contains=H,{name:"YAML",case_insensitive:!0,aliases:["yml"],contains:B}}z.registerLanguage("php",Qe);z.registerLanguage("php-template",Je);z.registerLanguage("javascript",it);z.registerLanguage("sql",at);z.registerLanguage("shell",st);z.registerLanguage("css",ot);z.registerLanguage("plaintext",ct);z.registerLanguage("xml",lt);z.registerLanguage("yaml",ut);var pe=z.getLanguage("sql");pe.keywords.keyword=Array.from(new Set([...pe.keywords.keyword,"if","ifnull","limit","aes_decrypt","aes_encrypt","ascii","bin","bit_and","bit_count","bit_length","bit_or","bit_xor","coercibility","concat","group_concat","concat_ws","connection_id","conv","curdate","curtime","database","date_add","date_format","date_sub","dayname","dayofmonth","dayofweek","dayofyear","elt","export_set","field","find_in_set","format","from_base64","from_days","from_unixtime","get_lock","greatest","hex","ifnull","inet_aton","inet_ntoa","instr","isnull","last_insert_id","least","lpad","ltrim","make_set","md5","monthname","now","oct","ord","password","quote","release_lock","repeat","replace","reverse","rpad","rtrim","sec_to_time","sha1","sha2","sleep","soundex","space","strcmp","str_to_date","substr","sysdate","time_format","time_to_sec","to_base64","to_days","unix_timestamp","updatexml","version","week","weekday","yearweek","length","substring_index","json_unquote","json_extract","json_contains"]));pe.keywords.type=Array.from(new Set([...pe.keywords.type,"longtext"]));z.configure({classPrefix:"phpdebugbar-hljs-"});globalThis.phpdebugbar_hljs=z.default;})();

(()=>{var b=Object.create;var y=Object.defineProperty;var j=Object.getOwnPropertyDescriptor;var $=Object.getOwnPropertyNames;var q=Object.getPrototypeOf,ee=Object.prototype.hasOwnProperty;var o=(E,e)=>()=>(e||E((e={exports:{}}).exports,e),e.exports);var Ee=(E,e,t,r)=>{if(e&&typeof e=="object"||typeof e=="function")for(let T of $(e))!ee.call(E,T)&&T!==t&&y(E,T,{get:()=>e[T],enumerable:!(r=j(e,T))||r.enumerable});return E};var te=(E,e,t)=>(t=E!=null?b(q(E)):{},Ee(e||!E||!E.__esModule?y(t,"default",{value:E,enumerable:!0}):t,E));var F=o(h=>{"use strict";h.__esModule=!0;var W=/[\\^$.*+?()[\]{}|]/g,re=RegExp(W.source);function Te(E){return E&&re.test(E)?E.replace(W,"\\$&"):E||""}h.default=Te});var C=o(a=>{"use strict";a.__esModule=!0;a.TokenTypes=void 0;var Re;(function(E){E.WHITESPACE="whitespace",E.WORD="word",E.STRING="string",E.RESERVED="reserved",E.RESERVED_TOP_LEVEL="reserved-top-level",E.RESERVED_TOP_LEVEL_NO_INDENT="reserved-top-level-no-indent",E.RESERVED_NEWLINE="reserved-newline",E.OPERATOR="operator",E.NO_SPACE_OPERATOR="no-space-operator",E.OPEN_PAREN="open-paren",E.CLOSE_PAREN="close-paren",E.LINE_COMMENT="line-comment",E.BLOCK_COMMENT="block-comment",E.NUMBER="number",E.PLACEHOLDER="placeholder",E.SERVERVARIABLE="servervariable"})(Re=a.TokenTypes||(a.TokenTypes={}))});var B=o(u=>{"use strict";var ne=u&&u.__importDefault||function(E){return E&&E.__esModule?E:{default:E}};u.__esModule=!0;var c=ne(F()),N=C(),Ne=(function(){function E(e){this.WHITESPACE_REGEX=/^(\s+)/u,this.NUMBER_REGEX=/^((-\s*)?[0-9]+(\.[0-9]+)?|0x[0-9a-fA-F]+|0b[01]+|([a-fA-F0-9]{8}-[a-fA-F0-9]{4}-[a-fA-F0-9]{4}-[a-fA-F0-9]{4}-[a-fA-F0-9]{12}))\b/u,this.AMBIGUOS_OPERATOR_REGEX=/^(\?\||\?&)/u,this.OPERATOR_REGEX=/^(!=|<>|>>|<<|==|<=|>=|!<|!>|\|\|\/|\|\/|\|\||~~\*|~~|!~~\*|!~~|~\*|!~\*|!~|:=|=>|&&|@>|<@|#-|@@|@|.)/u,this.NO_SPACE_OPERATOR_REGEX=/^(::|->>|->|#>>|#>)/u,this.BLOCK_COMMENT_REGEX=/^(\/\*[^]*?(?:\*\/|$))/u,this.LINE_COMMENT_REGEX=this.createLineCommentRegex(e.lineCommentTypes),this.RESERVED_TOP_LEVEL_REGEX=this.createReservedWordRegex(e.reservedTopLevelWords),this.RESERVED_TOP_LEVEL_NO_INDENT_REGEX=this.createReservedWordRegex(e.reservedTopLevelWordsNoIndent),this.RESERVED_NEWLINE_REGEX=this.createReservedWordRegex(e.reservedNewlineWords),this.RESERVED_PLAIN_REGEX=this.createReservedWordRegex(e.reservedWords),this.WORD_REGEX=this.createWordRegex(e.specialWordChars),this.STRING_REGEX=this.createStringRegex(e.stringTypes),this.OPEN_PAREN_REGEX=this.createParenRegex(e.openParens),this.CLOSE_PAREN_REGEX=this.createParenRegex(e.closeParens),this.INDEXED_PLACEHOLDER_REGEX=this.createPlaceholderRegex(e.indexedPlaceholderTypes,"[0-9]*"),this.IDENT_NAMED_PLACEHOLDER_REGEX=this.createPlaceholderRegex(e.namedPlaceholderTypes,"[a-zA-Z0-9._$]+"),this.STRING_NAMED_PLACEHOLDER_REGEX=this.createPlaceholderRegex(e.namedPlaceholderTypes,this.createStringPattern(e.stringTypes))}return E.prototype.createLineCommentRegex=function(e){var t="((?<!#)>|(?:[^>]))";return new RegExp("^((?:".concat(e.map(function(r){return(0,c.default)(r)}).join("|"),")").concat(t,`.*?(?:\r
|\r|
|$))`),"u")},E.prototype.createReservedWordRegex=function(e){var t=e.join("|").replace(/ /gu,"\\s+");return new RegExp("^(".concat(t,")\\b"),"iu")},E.prototype.createWordRegex=function(e){return new RegExp("^([\\p{Alphabetic}\\p{Mark}\\p{Decimal_Number}\\p{Connector_Punctuation}\\p{Join_Control}".concat(e.join(""),"]+)"),"u")},E.prototype.createStringRegex=function(e){return new RegExp("^("+this.createStringPattern(e)+")","u")},E.prototype.createStringPattern=function(e){var t={"``":"((`[^`]*($|`))+)","[]":"((\\[[^\\]]*($|\\]))(\\][^\\]]*($|\\]))*)",'""':'(("[^"\\\\]*(?:\\\\.[^"\\\\]*)*("|$))+)',"''":"(('[^'\\\\]*(?:\\\\.[^'\\\\]*)*('|$))+)","N''":"((N'[^N'\\\\]*(?:\\\\.[^N'\\\\]*)*('|$))+)","E''":"(((E|e)'[^'\\\\]*(?:\\\\.[^'\\\\]*)*('|$))+)"};return e.map(function(r){return t[r]}).join("|")},E.prototype.createParenRegex=function(e){var t=this;return new RegExp("^("+e.map(function(r){return t.escapeParen(r)}).join("|")+")","iu")},E.prototype.escapeParen=function(e){return e.length===1?(0,c.default)(e):"\\b"+e+"\\b"},E.prototype.createPlaceholderRegex=function(e,t){if(!e||e.length===0)return null;var r=e.map(c.default).join("|");return new RegExp("^((?:".concat(r,")(?:").concat(t,"))"),"u")},E.prototype.tokenize=function(e){if(!e)return[];for(var t=[],r;e.length;)r=this.getNextToken(e,r),e=e.substring(r.value.length),t.push(r);return t},E.prototype.getNextToken=function(e,t){return this.getWhitespaceToken(e)||this.getCommentToken(e)||this.getStringToken(e)||this.getOpenParenToken(e)||this.getCloseParenToken(e)||this.getAmbiguosOperatorToken(e)||this.getNoSpaceOperatorToken(e)||this.getServerVariableToken(e)||this.getPlaceholderToken(e)||this.getNumberToken(e)||this.getReservedWordToken(e,t)||this.getWordToken(e)||this.getOperatorToken(e)},E.prototype.getWhitespaceToken=function(e){return this.getTokenOnFirstMatch({input:e,type:N.TokenTypes.WHITESPACE,regex:this.WHITESPACE_REGEX})},E.prototype.getCommentToken=function(e){return this.getLineCommentToken(e)||this.getBlockCommentToken(e)},E.prototype.getLineCommentToken=function(e){return this.getTokenOnFirstMatch({input:e,type:N.TokenTypes.LINE_COMMENT,regex:this.LINE_COMMENT_REGEX})},E.prototype.getBlockCommentToken=function(e){return this.getTokenOnFirstMatch({input:e,type:N.TokenTypes.BLOCK_COMMENT,regex:this.BLOCK_COMMENT_REGEX})},E.prototype.getStringToken=function(e){return this.getTokenOnFirstMatch({input:e,type:N.TokenTypes.STRING,regex:this.STRING_REGEX})},E.prototype.getOpenParenToken=function(e){return this.getTokenOnFirstMatch({input:e,type:N.TokenTypes.OPEN_PAREN,regex:this.OPEN_PAREN_REGEX})},E.prototype.getCloseParenToken=function(e){return this.getTokenOnFirstMatch({input:e,type:N.TokenTypes.CLOSE_PAREN,regex:this.CLOSE_PAREN_REGEX})},E.prototype.getPlaceholderToken=function(e){return this.getIdentNamedPlaceholderToken(e)||this.getStringNamedPlaceholderToken(e)||this.getIndexedPlaceholderToken(e)},E.prototype.getServerVariableToken=function(e){return this.getTokenOnFirstMatch({input:e,type:N.TokenTypes.SERVERVARIABLE,regex:/(^@@\w+)/iu})},E.prototype.getIdentNamedPlaceholderToken=function(e){return this.getPlaceholderTokenWithKey({input:e,regex:this.IDENT_NAMED_PLACEHOLDER_REGEX,parseKey:function(t){return t.slice(1)}})},E.prototype.getStringNamedPlaceholderToken=function(e){var t=this;return this.getPlaceholderTokenWithKey({input:e,regex:this.STRING_NAMED_PLACEHOLDER_REGEX,parseKey:function(r){return t.getEscapedPlaceholderKey({key:r.slice(2,-1),quoteChar:r.slice(-1)})}})},E.prototype.getIndexedPlaceholderToken=function(e){return this.getPlaceholderTokenWithKey({input:e,regex:this.INDEXED_PLACEHOLDER_REGEX,parseKey:function(t){return t.slice(1)}})},E.prototype.getPlaceholderTokenWithKey=function(e){var t=e.input,r=e.regex,T=e.parseKey,R=this.getTokenOnFirstMatch({input:t,regex:r,type:N.TokenTypes.PLACEHOLDER});return R&&(R.key=T(R.value)),R},E.prototype.getEscapedPlaceholderKey=function(e){var t=e.key,r=e.quoteChar;return t.replace(new RegExp((0,c.default)("\\"+r),"gu"),r)},E.prototype.getNumberToken=function(e){return this.getTokenOnFirstMatch({input:e,type:N.TokenTypes.NUMBER,regex:this.NUMBER_REGEX})},E.prototype.getOperatorToken=function(e){return this.getTokenOnFirstMatch({input:e,type:N.TokenTypes.OPERATOR,regex:this.OPERATOR_REGEX})},E.prototype.getAmbiguosOperatorToken=function(e){return this.getTokenOnFirstMatch({input:e,type:N.TokenTypes.OPERATOR,regex:this.AMBIGUOS_OPERATOR_REGEX})},E.prototype.getNoSpaceOperatorToken=function(e){return this.getTokenOnFirstMatch({input:e,type:N.TokenTypes.NO_SPACE_OPERATOR,regex:this.NO_SPACE_OPERATOR_REGEX})},E.prototype.getReservedWordToken=function(e,t){if(!(t&&t.value&&t.value==="."))return this.getToplevelReservedToken(e)||this.getNewlineReservedToken(e)||this.getTopLevelReservedTokenNoIndent(e)||this.getPlainReservedToken(e)},E.prototype.getToplevelReservedToken=function(e){return this.getTokenOnFirstMatch({input:e,type:N.TokenTypes.RESERVED_TOP_LEVEL,regex:this.RESERVED_TOP_LEVEL_REGEX})},E.prototype.getNewlineReservedToken=function(e){return this.getTokenOnFirstMatch({input:e,type:N.TokenTypes.RESERVED_NEWLINE,regex:this.RESERVED_NEWLINE_REGEX})},E.prototype.getPlainReservedToken=function(e){return this.getTokenOnFirstMatch({input:e,type:N.TokenTypes.RESERVED,regex:this.RESERVED_PLAIN_REGEX})},E.prototype.getTopLevelReservedTokenNoIndent=function(e){return this.getTokenOnFirstMatch({input:e,type:N.TokenTypes.RESERVED_TOP_LEVEL_NO_INDENT,regex:this.RESERVED_TOP_LEVEL_NO_INDENT_REGEX})},E.prototype.getWordToken=function(e){return this.getTokenOnFirstMatch({input:e,type:N.TokenTypes.WORD,regex:this.WORD_REGEX})},E.prototype.getTokenOnFirstMatch=function(e){var t=e.input,r=e.type,T=e.regex,R=t.match(T);if(R)return{type:r,value:R[1]}},E})();u.default=Ne});var g=o(d=>{"use strict";d.__esModule=!0;var oe=function(E){return E===void 0&&(E=[]),E[E.length-1]};d.default=oe});var H=o(l=>{"use strict";var Ae=l&&l.__importDefault||function(E){return E&&E.__esModule?E:{default:E}};l.__esModule=!0;var ie=Ae(g()),M="top-level",Ie="block-level",Oe=(function(){function E(e){this.indent=e,this.indentTypes=[],this.indent=e||"  "}return E.prototype.getIndent=function(){return new Array(this.indentTypes.length).fill(this.indent).join("")},E.prototype.increaseTopLevel=function(){this.indentTypes.push(M)},E.prototype.increaseBlockLevel=function(){this.indentTypes.push(Ie)},E.prototype.decreaseTopLevel=function(){(0,ie.default)(this.indentTypes)===M&&this.indentTypes.pop()},E.prototype.decreaseBlockLevel=function(){for(;this.indentTypes.length>0;){var e=this.indentTypes.pop();if(e!==M)break}},E.prototype.resetIndentation=function(){this.indentTypes=[]},E})();l.default=Oe});var V=o(f=>{"use strict";f.__esModule=!0;var S=C(),se=50,Se=(function(){function E(){this.level=0}return E.prototype.beginIfPossible=function(e,t){this.level===0&&this.isInlineBlock(e,t)?this.level=1:this.level>0?this.level++:this.level=0},E.prototype.end=function(){this.level--},E.prototype.isActive=function(){return this.level>0},E.prototype.isInlineBlock=function(e,t){for(var r=0,T=0,R=t;R<e.length;R++){var P=e[R];if(r+=P.value.length,r>se)return!1;if(P.type===S.TokenTypes.OPEN_PAREN)T++;else if(P.type===S.TokenTypes.CLOSE_PAREN&&(T--,T===0))return!0;if(this.isForbiddenToken(P))return!1}return!1},E.prototype.isForbiddenToken=function(e){var t=e.type,r=e.value;return t===S.TokenTypes.RESERVED_TOP_LEVEL||t===S.TokenTypes.RESERVED_NEWLINE||t===S.TokenTypes.LINE_COMMENT||t===S.TokenTypes.BLOCK_COMMENT||r===";"},E})();f.default=Se});var m=o(v=>{"use strict";v.__esModule=!0;var Le=(function(){function E(e){this.params=e,this.index=0,this.params=e}return E.prototype.get=function(e){var t=e.key,r=e.value;return this.params?t?this.params[t]:this.params[this.index++]:r},E})();v.default=Le});var Y=o(p=>{"use strict";var G=p&&p.__importDefault||function(E){return E&&E.__esModule?E:{default:E}};p.__esModule=!0;var n=C(),ae=G(H()),Ce=G(V()),ue=G(m()),le=[" ","	"],L=function(E){for(var e=E.length-1;e>=0&&le.includes(E[e]);)e--;return E.substring(0,e+1)},pe=(function(){function E(e,t,r){this.cfg=e,this.tokenizer=t,this.tokenOverride=r,this.tokens=[],this.previousReservedWord={type:null,value:null},this.previousNonWhiteSpace={type:null,value:null},this.index=0,this.indentation=new ae.default(this.cfg.indent),this.inlineBlock=new Ce.default,this.params=new ue.default(this.cfg.params)}return E.prototype.format=function(e){this.tokens=this.tokenizer.tokenize(e);var t=this.getFormattedQueryFromTokens();return t.trim()},E.prototype.getFormattedQueryFromTokens=function(){var e=this,t="";return this.tokens.forEach(function(r,T){e.index=T,e.tokenOverride&&(r=e.tokenOverride(r,e.previousReservedWord)||r),r.type===n.TokenTypes.WHITESPACE?t=e.formatWhitespace(r,t):r.type===n.TokenTypes.LINE_COMMENT?t=e.formatLineComment(r,t):r.type===n.TokenTypes.BLOCK_COMMENT?t=e.formatBlockComment(r,t):r.type===n.TokenTypes.RESERVED_TOP_LEVEL||r.type===n.TokenTypes.RESERVED_TOP_LEVEL_NO_INDENT||r.type===n.TokenTypes.RESERVED_NEWLINE||r.type===n.TokenTypes.RESERVED?t=e.formatReserved(r,t):r.type===n.TokenTypes.OPEN_PAREN?t=e.formatOpeningParentheses(r,t):r.type===n.TokenTypes.CLOSE_PAREN?t=e.formatClosingParentheses(r,t):r.type===n.TokenTypes.NO_SPACE_OPERATOR?t=e.formatWithoutSpaces(r,t):r.type===n.TokenTypes.PLACEHOLDER||r.type===n.TokenTypes.SERVERVARIABLE?t=e.formatPlaceholder(r,t):r.value===","?t=e.formatComma(r,t):r.value===":"?t=e.formatWithSpaceAfter(r,t):r.value==="."?t=e.formatWithoutSpaces(r,t):r.value===";"?t=e.formatQuerySeparator(r,t):t=e.formatWithSpaces(r,t),r.type!==n.TokenTypes.WHITESPACE&&(e.previousNonWhiteSpace=r)}),t},E.prototype.formatWhitespace=function(e,t){return this.cfg.linesBetweenQueries==="preserve"&&/((\r\n|\n)(\r\n|\n)+)/u.test(e.value)&&this.previousToken().value===";"?t.replace(/(\n|\r\n)$/u,"")+e.value:t},E.prototype.formatReserved=function(e,t){return e.type===n.TokenTypes.RESERVED_NEWLINE&&this.previousReservedWord&&this.previousReservedWord.value&&e.value.toUpperCase()==="AND"&&this.previousReservedWord.value.toUpperCase()==="BETWEEN"&&(e.type=n.TokenTypes.RESERVED),e.type===n.TokenTypes.RESERVED_TOP_LEVEL?t=this.formatTopLevelReservedWord(e,t):e.type===n.TokenTypes.RESERVED_TOP_LEVEL_NO_INDENT?t=this.formatTopLevelReservedWordNoIndent(e,t):e.type===n.TokenTypes.RESERVED_NEWLINE?t=this.formatNewlineReservedWord(e,t):t=this.formatWithSpaces(e,t),this.previousReservedWord=e,t},E.prototype.formatLineComment=function(e,t){return this.addNewline(t+e.value)},E.prototype.formatBlockComment=function(e,t){return this.addNewline(this.addNewline(t)+this.indentComment(e.value))},E.prototype.indentComment=function(e){return e.replace(/\n[ \t]*/gu,`
`+this.indentation.getIndent()+" ")},E.prototype.formatTopLevelReservedWordNoIndent=function(e,t){return this.indentation.decreaseTopLevel(),t=this.addNewline(t)+this.equalizeWhitespace(this.formatReservedWord(e.value)),this.addNewline(t)},E.prototype.formatTopLevelReservedWord=function(e,t){var r=this.previousNonWhiteSpace.value!==","&&!["GRANT"].includes("".concat(this.previousNonWhiteSpace.value).toUpperCase());return r&&(this.indentation.decreaseTopLevel(),t=this.addNewline(t)),t=t+this.equalizeWhitespace(this.formatReservedWord(e.value))+" ",r&&this.indentation.increaseTopLevel(),t},E.prototype.formatNewlineReservedWord=function(e,t){return this.addNewline(t)+this.equalizeWhitespace(this.formatReservedWord(e.value))+" "},E.prototype.equalizeWhitespace=function(e){return e.replace(/\s+/gu," ")},E.prototype.formatOpeningParentheses=function(e,t){e.value=this.formatCase(e.value);var r=this.previousToken().type;return r!==n.TokenTypes.WHITESPACE&&r!==n.TokenTypes.OPEN_PAREN&&r!==n.TokenTypes.LINE_COMMENT&&(t=L(t)),t+=e.value,this.inlineBlock.beginIfPossible(this.tokens,this.index),this.inlineBlock.isActive()||(this.indentation.increaseBlockLevel(),t=this.addNewline(t)),t},E.prototype.formatClosingParentheses=function(e,t){return e.value=this.formatCase(e.value),this.inlineBlock.isActive()?(this.inlineBlock.end(),this.formatWithSpaceAfter(e,t)):(this.indentation.decreaseBlockLevel(),this.formatWithSpaces(e,this.addNewline(t)))},E.prototype.formatPlaceholder=function(e,t){return t+this.params.get(e)+" "},E.prototype.formatComma=function(e,t){return t=L(t)+e.value+" ",this.inlineBlock.isActive()||/^LIMIT$/iu.test(this.previousReservedWord.value)?t:this.addNewline(t)},E.prototype.formatWithSpaceAfter=function(e,t){return L(t)+e.value+" "},E.prototype.formatWithoutSpaces=function(e,t){return L(t)+e.value},E.prototype.formatWithSpaces=function(e,t){var r=e.type===n.TokenTypes.RESERVED?this.formatReservedWord(e.value):e.value;return t+r+" "},E.prototype.formatReservedWord=function(e){return this.formatCase(e)},E.prototype.formatQuerySeparator=function(e,t){this.indentation.resetIndentation();var r=`
`;return this.cfg.linesBetweenQueries!=="preserve"&&(r=`
`.repeat(this.cfg.linesBetweenQueries||1)),L(t)+e.value+r},E.prototype.addNewline=function(e){return e=L(e),e.endsWith(`
`)||(e+=`
`),e+this.indentation.getIndent()},E.prototype.previousToken=function(){return this.tokens[this.index-1]||{type:null,value:null}},E.prototype.formatCase=function(e){return this.cfg.reservedWordCase==="upper"?e.toUpperCase():this.cfg.reservedWordCase==="lower"?e.toLowerCase():e},E})();p.default=pe});var D=o(_=>{"use strict";var X=_&&_.__importDefault||function(E){return E&&E.__esModule?E:{default:E}};_.__esModule=!0;var _e=X(B()),De=X(Y()),Pe=(function(){function E(e){this.cfg=e}return E.prototype.format=function(e){return new De.default(this.cfg,this.tokenizer(),this.tokenOverride).format(e)},E.prototype.tokenize=function(e){return this.tokenizer().tokenize(e)},E.prototype.tokenizer=function(){return new _e.default(this.getTokenizerConfig())},E})();_.default=Pe});var k=o(i=>{"use strict";var ce=i&&i.__extends||(function(){var E=function(e,t){return E=Object.setPrototypeOf||{__proto__:[]}instanceof Array&&function(r,T){r.__proto__=T}||function(r,T){for(var R in T)Object.prototype.hasOwnProperty.call(T,R)&&(r[R]=T[R])},E(e,t)};return function(e,t){if(typeof t!="function"&&t!==null)throw new TypeError("Class extends value "+String(t)+" is not a constructor or null");E(e,t);function r(){this.constructor=e}e.prototype=t===null?Object.create(t):(r.prototype=t.prototype,new r)}})(),Ue=i&&i.__importDefault||function(E){return E&&E.__esModule?E:{default:E}};i.__esModule=!0;var he=Ue(D()),de=(function(E){ce(e,E);function e(){return E!==null&&E.apply(this,arguments)||this}return e.prototype.getTokenizerConfig=function(){return{reservedWords:Me,reservedTopLevelWords:fe,reservedNewlineWords:Ge,reservedTopLevelWordsNoIndent:ve,stringTypes:['""',"''","``","[]"],openParens:["("],closeParens:[")"],indexedPlaceholderTypes:["?"],namedPlaceholderTypes:[":"],lineCommentTypes:["--"],specialWordChars:["#","@"]}},e})(he.default);i.default=de;var Me=["ABS","ACTIVATE","ALIAS","ALL","ALLOCATE","ALLOW","ALTER","ANY","ARE","ARRAY","AS","ASC","ASENSITIVE","ASSOCIATE","ASUTIME","ASYMMETRIC","AT","ATOMIC","ATTRIBUTES","AUDIT","AUTHORIZATION","AUX","AUXILIARY","AVG","BEFORE","BEGIN","BETWEEN","BIGINT","BINARY","BLOB","BOOLEAN","BOTH","BUFFERPOOL","BY","CACHE","CALL","CALLED","CAPTURE","CARDINALITY","CASCADED","CASE","CAST","CCSID","CEIL","CEILING","CHAR","CHARACTER","CHARACTER_LENGTH","CHAR_LENGTH","CHECK","CLOB","CLONE","CLOSE","CLUSTER","COALESCE","COLLATE","COLLECT","COLLECTION","COLLID","COLUMN","COMMENT","COMMIT","CONCAT","CONDITION","CONNECT","CONNECTION","CONSTRAINT","CONTAINS","CONTINUE","CONVERT","CORR","CORRESPONDING","COUNT","COUNT_BIG","COVAR_POP","COVAR_SAMP","CREATE","CROSS","CUBE","CUME_DIST","CURRENT","CURRENT_DATE","CURRENT_DEFAULT_TRANSFORM_GROUP","CURRENT_LC_CTYPE","CURRENT_PATH","CURRENT_ROLE","CURRENT_SCHEMA","CURRENT_SERVER","CURRENT_TIME","CURRENT_TIMESTAMP","CURRENT_TIMEZONE","CURRENT_TRANSFORM_GROUP_FOR_TYPE","CURRENT_USER","CURSOR","CYCLE","DATA","DATABASE","DATAPARTITIONNAME","DATAPARTITIONNUM","DATE","DAY","DAYS","DB2GENERAL","DB2GENRL","DB2SQL","DBINFO","DBPARTITIONNAME","DBPARTITIONNUM","DEALLOCATE","DEC","DECIMAL","DECLARE","DEFAULT","DEFAULTS","DEFINITION","DELETE","DENSERANK","DENSE_RANK","DEREF","DESCRIBE","DESCRIPTOR","DETERMINISTIC","DIAGNOSTICS","DISABLE","DISALLOW","DISCONNECT","DISTINCT","DO","DOCUMENT","DOUBLE","DROP","DSSIZE","DYNAMIC","EACH","EDITPROC","ELEMENT","ELSE","ELSEIF","ENABLE","ENCODING","ENCRYPTION","END","END-EXEC","ENDING","ERASE","ESCAPE","EVERY","EXCEPTION","EXCLUDING","EXCLUSIVE","EXEC","EXECUTE","EXISTS","EXIT","EXP","EXPLAIN","EXTENDED","EXTERNAL","EXTRACT","FALSE","FENCED","FETCH","FIELDPROC","FILE","FILTER","FINAL","FIRST","FLOAT","FLOOR","FOR","FOREIGN","FREE","FULL","FUNCTION","FUSION","GENERAL","GENERATED","GET","GLOBAL","GOTO","GRANT","GRAPHIC","GROUP","GROUPING","HANDLER","HASH","HASHED_VALUE","HINT","HOLD","HOUR","HOURS","IDENTITY","IF","IMMEDIATE","IN","INCLUDING","INCLUSIVE","INCREMENT","INDEX","INDICATOR","INDICATORS","INF","INFINITY","INHERIT","INNER","INOUT","INSENSITIVE","INSERT","INT","INTEGER","INTEGRITY","INTERSECTION","INTERVAL","INTO","IS","ISOBID","ISOLATION","ITERATE","JAR","JAVA","KEEP","KEY","LABEL","LANGUAGE","LARGE","LATERAL","LC_CTYPE","LEADING","LEAVE","LEFT","LIKE","LINKTYPE","LN","LOCAL","LOCALDATE","LOCALE","LOCALTIME","LOCALTIMESTAMP","LOCATOR","LOCATORS","LOCK","LOCKMAX","LOCKSIZE","LONG","LOOP","LOWER","MAINTAINED","MATCH","MATERIALIZED","MAX","MAXVALUE","MEMBER","MERGE","METHOD","MICROSECOND","MICROSECONDS","MIN","MINUTE","MINUTES","MINVALUE","MOD","MODE","MODIFIES","MODULE","MONTH","MONTHS","MULTISET","NAN","NATIONAL","NATURAL","NCHAR","NCLOB","NEW","NEW_TABLE","NEXTVAL","NO","NOCACHE","NOCYCLE","NODENAME","NODENUMBER","NOMAXVALUE","NOMINVALUE","NONE","NOORDER","NORMALIZE","NORMALIZED","NOT","NULL","NULLIF","NULLS","NUMERIC","NUMPARTS","OBID","OCTET_LENGTH","OF","OFFSET","OLD","OLD_TABLE","ON","ONLY","OPEN","OPTIMIZATION","OPTIMIZE","OPTION","ORDER","OUT","OUTER","OVER","OVERLAPS","OVERLAY","OVERRIDING","PACKAGE","PADDED","PAGESIZE","PARAMETER","PART","PARTITION","PARTITIONED","PARTITIONING","PARTITIONS","PASSWORD","PATH","PERCENTILE_CONT","PERCENTILE_DISC","PERCENT_RANK","PIECESIZE","PLAN","POSITION","POWER","PRECISION","PREPARE","PREVVAL","PRIMARY","PRIQTY","PRIVILEGES","PROCEDURE","PROGRAM","PSID","PUBLIC","QUERY","QUERYNO","RANGE","RANK","READ","READS","REAL","RECOVERY","RECURSIVE","REF","REFERENCES","REFERENCING","REFRESH","REGR_AVGX","REGR_AVGY","REGR_COUNT","REGR_INTERCEPT","REGR_R2","REGR_SLOPE","REGR_SXX","REGR_SXY","REGR_SYY","RELEASE","RENAME","REPEAT","RESET","RESIGNAL","RESTART","RESTRICT","RESULT","RESULT_SET_LOCATOR","RETURN","RETURNS","REVOKE","RIGHT","ROLE","ROLLBACK","ROLLUP","ROUND_CEILING","ROUND_DOWN","ROUND_FLOOR","ROUND_HALF_DOWN","ROUND_HALF_EVEN","ROUND_HALF_UP","ROUND_UP","ROUTINE","ROW","ROWNUMBER","ROWS","ROWSET","ROW_NUMBER","RRN","RUN","SAVEPOINT","SCHEMA","SCOPE","SCRATCHPAD","SCROLL","SEARCH","SECOND","SECONDS","SECQTY","SECURITY","SENSITIVE","SEQUENCE","SESSION","SESSION_USER","SIGNAL","SIMILAR","SIMPLE","SMALLINT","SNAN","SOME","SOURCE","SPECIFIC","SPECIFICTYPE","SQL","SQLEXCEPTION","SQLID","SQLSTATE","SQLWARNING","SQRT","STACKED","STANDARD","START","STARTING","STATEMENT","STATIC","STATMENT","STAY","STDDEV_POP","STDDEV_SAMP","STOGROUP","STORES","STYLE","SUBMULTISET","SUBSTRING","SUM","SUMMARY","SYMMETRIC","SYNONYM","SYSFUN","SYSIBM","SYSPROC","SYSTEM","SYSTEM_USER","TABLE","TABLESAMPLE","TABLESPACE","THEN","TIME","TIMESTAMP","TIMEZONE_HOUR","TIMEZONE_MINUTE","TO","TRAILING","TRANSACTION","TRANSLATE","TRANSLATION","TREAT","TRIGGER","TRIM","TRUE","TRUNCATE","TYPE","UESCAPE","UNDO","UNIQUE","UNKNOWN","UNNEST","UNTIL","UPPER","USAGE","USER","USING","VALIDPROC","VALUE","VARCHAR","VARIABLE","VARIANT","VARYING","VAR_POP","VAR_SAMP","VCAT","VERSION","VIEW","VOLATILE","VOLUMES","WHEN","WHENEVER","WHILE","WIDTH_BUCKET","WINDOW","WITH","WITHIN","WITHOUT","WLM","WRITE","XMLELEMENT","XMLEXISTS","XMLNAMESPACES","YEAR","YEARS"],fe=["ADD","AFTER","ALTER COLUMN","ALTER TABLE","DELETE FROM","EXCEPT","FETCH FIRST","FROM","GROUP BY","GO","HAVING","INSERT INTO","INTERSECT","LIMIT","ORDER BY","SELECT","SET CURRENT SCHEMA","SET SCHEMA","SET","UPDATE","VALUES","WHERE"],ve=["INTERSECT","INTERSECT ALL","MINUS","UNION","UNION ALL"],Ge=["AND","CROSS JOIN","INNER JOIN","JOIN","LEFT JOIN","LEFT OUTER JOIN","OR","OUTER JOIN","RIGHT JOIN","RIGHT OUTER JOIN"]});var K=o(I=>{"use strict";var ye=I&&I.__extends||(function(){var E=function(e,t){return E=Object.setPrototypeOf||{__proto__:[]}instanceof Array&&function(r,T){r.__proto__=T}||function(r,T){for(var R in T)Object.prototype.hasOwnProperty.call(T,R)&&(r[R]=T[R])},E(e,t)};return function(e,t){if(typeof t!="function"&&t!==null)throw new TypeError("Class extends value "+String(t)+" is not a constructor or null");E(e,t);function r(){this.constructor=e}e.prototype=t===null?Object.create(t):(r.prototype=t.prototype,new r)}})(),We=I&&I.__importDefault||function(E){return E&&E.__esModule?E:{default:E}};I.__esModule=!0;var Fe=We(D()),Be=(function(E){ye(e,E);function e(){return E!==null&&E.apply(this,arguments)||this}return e.prototype.getTokenizerConfig=function(){return{reservedWords:ge,reservedTopLevelWords:He,reservedNewlineWords:me,reservedTopLevelWordsNoIndent:Ve,stringTypes:['""',"''","``"],openParens:["(","[","{"],closeParens:[")","]","}"],namedPlaceholderTypes:["$"],lineCommentTypes:["#","--"],specialWordChars:[]}},e})(Fe.default);I.default=Be;var ge=["ALL","ALTER","ANALYZE","AND","ANY","ARRAY","AS","ASC","BEGIN","BETWEEN","BINARY","BOOLEAN","BREAK","BUCKET","BUILD","BY","CALL","CASE","CAST","CLUSTER","COLLATE","COLLECTION","COMMIT","CONNECT","CONTINUE","CORRELATE","COVER","CREATE","DATABASE","DATASET","DATASTORE","DECLARE","DECREMENT","DELETE","DERIVED","DESC","DESCRIBE","DISTINCT","DO","DROP","EACH","ELEMENT","ELSE","END","EVERY","EXCEPT","EXCLUDE","EXECUTE","EXISTS","EXPLAIN","FALSE","FETCH","FIRST","FLATTEN","FOR","FORCE","FROM","FUNCTION","GRANT","GROUP","GSI","HAVING","IF","IGNORE","ILIKE","IN","INCLUDE","INCREMENT","INDEX","INFER","INLINE","INNER","INSERT","INTERSECT","INTO","IS","JOIN","KEY","KEYS","KEYSPACE","KNOWN","LAST","LEFT","LET","LETTING","LIKE","LIMIT","LSM","MAP","MAPPING","MATCHED","MATERIALIZED","MERGE","MISSING","NAMESPACE","NEST","NOT","NULL","NUMBER","OBJECT","OFFSET","ON","OPTION","OR","ORDER","OUTER","OVER","PARSE","PARTITION","PASSWORD","PATH","POOL","PREPARE","PRIMARY","PRIVATE","PRIVILEGE","PROCEDURE","PUBLIC","RAW","REALM","REDUCE","RENAME","RETURN","RETURNING","REVOKE","RIGHT","ROLE","ROLLBACK","SATISFIES","SCHEMA","SELECT","SELF","SEMI","SET","SHOW","SOME","START","STATISTICS","STRING","SYSTEM","THEN","TO","TRANSACTION","TRIGGER","TRUE","TRUNCATE","UNDER","UNION","UNIQUE","UNKNOWN","UNNEST","UNSET","UPDATE","UPSERT","USE","USER","USING","VALIDATE","VALUE","VALUED","VALUES","VIA","VIEW","WHEN","WHERE","WHILE","WITH","WITHIN","WORK","XOR"],He=["DELETE FROM","EXCEPT ALL","EXCEPT","EXPLAIN DELETE FROM","EXPLAIN UPDATE","EXPLAIN UPSERT","FROM","GROUP BY","HAVING","INFER","INSERT INTO","LET","LIMIT","MERGE","NEST","ORDER BY","PREPARE","SELECT","SET CURRENT SCHEMA","SET SCHEMA","SET","UNNEST","UPDATE","UPSERT","USE KEYS","VALUES","WHERE"],Ve=["INTERSECT","INTERSECT ALL","MINUS","UNION","UNION ALL"],me=["AND","INNER JOIN","JOIN","LEFT JOIN","LEFT OUTER JOIN","OR","OUTER JOIN","RIGHT JOIN","RIGHT OUTER JOIN","XOR"]});var x=o(O=>{"use strict";var Ye=O&&O.__extends||(function(){var E=function(e,t){return E=Object.setPrototypeOf||{__proto__:[]}instanceof Array&&function(r,T){r.__proto__=T}||function(r,T){for(var R in T)Object.prototype.hasOwnProperty.call(T,R)&&(r[R]=T[R])},E(e,t)};return function(e,t){if(typeof t!="function"&&t!==null)throw new TypeError("Class extends value "+String(t)+" is not a constructor or null");E(e,t);function r(){this.constructor=e}e.prototype=t===null?Object.create(t):(r.prototype=t.prototype,new r)}})(),Xe=O&&O.__importDefault||function(E){return E&&E.__esModule?E:{default:E}};O.__esModule=!0;var ke=Xe(D()),w=C(),Ke=(function(E){Ye(e,E);function e(){var t=E!==null&&E.apply(this,arguments)||this;return t.tokenOverride=function(r,T){if(r.type===w.TokenTypes.RESERVED_TOP_LEVEL&&T.value&&r.value.toUpperCase()==="SET"&&T.value.toUpperCase()==="BY")return r.type=w.TokenTypes.RESERVED,r},t}return e.prototype.getTokenizerConfig=function(){return{reservedWords:we,reservedTopLevelWords:xe,reservedNewlineWords:Je,reservedTopLevelWordsNoIndent:Qe,stringTypes:['""',"N''","''","``"],openParens:["(","CASE"],closeParens:[")","END"],indexedPlaceholderTypes:["?"],namedPlaceholderTypes:[":"],lineCommentTypes:["--"],specialWordChars:["_","$","#",".","@"]}},e})(ke.default);O.default=Ke;var we=["A","ACCESSIBLE","AGENT","AGGREGATE","ALL","ALTER","ANY","ARRAY","AS","ASC","AT","ATTRIBUTE","AUTHID","AVG","BETWEEN","BFILE_BASE","BINARY_INTEGER","BINARY","BLOB_BASE","BLOCK","BODY","BOOLEAN","BOTH","BOUND","BREADTH","BULK","BY","BYTE","C","CALL","CALLING","CASCADE","CASE","CHAR_BASE","CHAR","CHARACTER","CHARSET","CHARSETFORM","CHARSETID","CHECK","CLOB_BASE","CLONE","CLOSE","CLUSTER","CLUSTERS","COALESCE","COLAUTH","COLLECT","COLUMNS","COMMENT","COMMIT","COMMITTED","COMPILED","COMPRESS","CONNECT","CONSTANT","CONSTRUCTOR","CONTEXT","CONTINUE","CONVERT","COUNT","CRASH","CREATE","CREDENTIAL","CURRENT","CURRVAL","CURSOR","CUSTOMDATUM","DANGLING","DATA","DATE_BASE","DATE","DAY","DECIMAL","DEFAULT","DEFINE","DELETE","DEPTH","DESC","DETERMINISTIC","DIRECTORY","DISTINCT","DO","DOUBLE","DROP","DURATION","ELEMENT","ELSIF","EMPTY","END","ESCAPE","EXCEPTIONS","EXCLUSIVE","EXECUTE","EXISTS","EXIT","EXTENDS","EXTERNAL","EXTRACT","FALSE","FETCH","FINAL","FIRST","FIXED","FLOAT","FOR","FORALL","FORCE","FROM","FUNCTION","GENERAL","GOTO","GRANT","GROUP","HASH","HEAP","HIDDEN","HOUR","IDENTIFIED","IF","IMMEDIATE","IN","INCLUDING","INDEX","INDEXES","INDICATOR","INDICES","INFINITE","INSTANTIABLE","INT","INTEGER","INTERFACE","INTERVAL","INTO","INVALIDATE","IS","ISOLATION","JAVA","LANGUAGE","LARGE","LEADING","LENGTH","LEVEL","LIBRARY","LIKE","LIKE2","LIKE4","LIKEC","LIMITED","LOCAL","LOCK","LONG","MAP","MAX","MAXLEN","MEMBER","MERGE","MIN","MINUTE","MLSLABEL","MOD","MODE","MONTH","MULTISET","NAME","NAN","NATIONAL","NATIVE","NATURAL","NATURALN","NCHAR","NEW","NEXTVAL","NOCOMPRESS","NOCOPY","NOT","NOWAIT","NULL","NULLIF","NUMBER_BASE","NUMBER","OBJECT","OCICOLL","OCIDATE","OCIDATETIME","OCIDURATION","OCIINTERVAL","OCILOBLOCATOR","OCINUMBER","OCIRAW","OCIREF","OCIREFCURSOR","OCIROWID","OCISTRING","OCITYPE","OF","OLD","ON","ONLY","OPAQUE","OPEN","OPERATOR","OPTION","ORACLE","ORADATA","ORDER","ORGANIZATION","ORLANY","ORLVARY","OTHERS","OUT","OVERLAPS","OVERRIDING","PACKAGE","PARALLEL_ENABLE","PARAMETER","PARAMETERS","PARENT","PARTITION","PASCAL","PCTFREE","PIPE","PIPELINED","PLS_INTEGER","PLUGGABLE","POSITIVE","POSITIVEN","PRAGMA","PRECISION","PRIOR","PRIVATE","PROCEDURE","PUBLIC","RAISE","RANGE","RAW","READ","REAL","RECORD","REF","REFERENCE","RELEASE","RELIES_ON","REM","REMAINDER","RENAME","RESOURCE","RESULT_CACHE","RESULT","RETURN","RETURNING","REVERSE","REVOKE","ROLLBACK","ROW","ROWID","ROWNUM","ROWTYPE","SAMPLE","SAVE","SAVEPOINT","SB1","SB2","SB4","SEARCH","SECOND","SEGMENT","SELF","SEPARATE","SEQUENCE","SERIALIZABLE","SHARE","SHORT","SIZE_T","SIZE","SMALLINT","SOME","SPACE","SPARSE","SQL","SQLCODE","SQLDATA","SQLERRM","SQLNAME","SQLSTATE","STANDARD","START","STATIC","STDDEV","STORED","STRING","STRUCT","STYLE","SUBMULTISET","SUBPARTITION","SUBSTITUTABLE","SUBTYPE","SUCCESSFUL","SUM","SYNONYM","SYSDATE","TABAUTH","TABLE","TDO","THE","THEN","TIME","TIMESTAMP","TIMEZONE_ABBR","TIMEZONE_HOUR","TIMEZONE_MINUTE","TIMEZONE_REGION","TO","TRAILING","TRANSACTION","TRANSACTIONAL","TRIGGER","TRUE","TRUSTED","TYPE","UB1","UB2","UB4","UID","UNDER","UNIQUE","UNPLUG","UNSIGNED","UNTRUSTED","USE","USER","USING","VALIDATE","VALIST","VALUE","VARCHAR","VARCHAR2","VARIABLE","VARIANCE","VARRAY","VARYING","VIEW","VIEWS","VOID","WHENEVER","WHILE","WITH","WORK","WRAPPED","WRITE","YEAR","ZONE"],xe=["ADD","ALTER COLUMN","ALTER TABLE","BEGIN","CONNECT BY","DECLARE","DELETE FROM","DELETE","END","EXCEPT","EXCEPTION","FETCH FIRST","FROM","GROUP BY","HAVING","INSERT INTO","INSERT","LIMIT","LOOP","MODIFY","ORDER BY","SELECT","SET CURRENT SCHEMA","SET SCHEMA","SET","START WITH","UPDATE","VALUES","WHERE"],Qe=["INTERSECT","INTERSECT ALL","MINUS","UNION","UNION ALL"],Je=["AND","CROSS APPLY","CROSS JOIN","ELSE","END","INNER JOIN","JOIN","LEFT JOIN","LEFT OUTER JOIN","OR","OUTER APPLY","OUTER JOIN","RIGHT JOIN","RIGHT OUTER JOIN","WHEN","XOR"]});var Q=o(s=>{"use strict";var Ze=s&&s.__extends||(function(){var E=function(e,t){return E=Object.setPrototypeOf||{__proto__:[]}instanceof Array&&function(r,T){r.__proto__=T}||function(r,T){for(var R in T)Object.prototype.hasOwnProperty.call(T,R)&&(r[R]=T[R])},E(e,t)};return function(e,t){if(typeof t!="function"&&t!==null)throw new TypeError("Class extends value "+String(t)+" is not a constructor or null");E(e,t);function r(){this.constructor=e}e.prototype=t===null?Object.create(t):(r.prototype=t.prototype,new r)}})(),ze=s&&s.__importDefault||function(E){return E&&E.__esModule?E:{default:E}};s.__esModule=!0;var be=ze(D()),je=(function(E){Ze(e,E);function e(){return E!==null&&E.apply(this,arguments)||this}return e.prototype.getTokenizerConfig=function(){return{reservedWords:$e,reservedTopLevelWords:qe,reservedNewlineWords:EE,reservedTopLevelWordsNoIndent:eE,stringTypes:['""',"N''","''","``","[]","E''"],openParens:["(","CASE"],closeParens:[")","END"],indexedPlaceholderTypes:["?"],namedPlaceholderTypes:["@",":","%","$"],lineCommentTypes:["#","--"],specialWordChars:[]}},e})(be.default);s.default=je;var $e=["ACCESSIBLE","ACTION","AGAINST","AGGREGATE","ALGORITHM","ALL","ALTER","ANALYSE","ANALYZE","AS","ASC","AUTOCOMMIT","AUTO_INCREMENT","BACKUP","BEGIN","BETWEEN","BINLOG","BOTH","CASCADE","CASE","CHANGE","CHANGED","CHARACTER SET","CHARSET","CHECK","CHECKSUM","COLLATE","COLLATION","COLUMN","COLUMNS","COMMENT","COMMIT","COMMITTED","COMPRESSED","CONCURRENT","CONSTRAINT","CONTAINS","CONVERT","COUNT","CREATE","CROSS","CURRENT_TIMESTAMP","DATABASE","DATABASES","DAY_HOUR","DAY_MINUTE","DAY_SECOND","DAY","DEFAULT","DEFINER","DELAYED","DELETE","DESC","DESCRIBE","DETERMINISTIC","DISTINCT","DISTINCTROW","DIV","DO","DROP","DUMPFILE","DUPLICATE","DYNAMIC","ELSE","ENCLOSED","END","ENGINE","ENGINES","ENGINE_TYPE","ESCAPE","ESCAPED","EVENTS","EXEC","EXECUTE","EXISTS","EXPLAIN","EXTENDED","FAST","FETCH","FIELDS","FILE","FIRST","FIXED","FLUSH","FOR","FORCE","FOREIGN","FULL","FULLTEXT","FUNCTION","GLOBAL","GRANTS","GROUP_CONCAT","HEAP","HIGH_PRIORITY","HOSTS","HOUR","HOUR_MINUTE","HOUR_SECOND","IDENTIFIED","IF","IFNULL","IGNORE","IN","INDEX","INDEXES","INFILE","INSERT","INSERT_ID","INSERT_METHOD","INTERVAL","INTO","INVOKER","IS","ISOLATION","KEY","KEYS","KILL","LAST_INSERT_ID","LEADING","LEVEL","LIKE","LINEAR","LINES","LOAD","LOCAL","LOCK","LOCKS","LOGS","LOW_PRIORITY","MARIA","MASTER","MASTER_CONNECT_RETRY","MASTER_HOST","MASTER_LOG_FILE","MATCH","MAX_CONNECTIONS_PER_HOUR","MAX_QUERIES_PER_HOUR","MAX_ROWS","MAX_UPDATES_PER_HOUR","MAX_USER_CONNECTIONS","MEDIUM","MERGE","MINUTE","MINUTE_SECOND","MIN_ROWS","MODE","MONTH","MRG_MYISAM","MYISAM","NAMES","NATURAL","NOT","NOW()","NULL","OFFSET","ON DELETE","ON UPDATE","ON","ONLY","OPEN","OPTIMIZE","OPTION","OPTIONALLY","OUTFILE","PACK_KEYS","PAGE","PARTIAL","PARTITION","PARTITIONS","PASSWORD","PRIMARY","PRIVILEGES","PROCEDURE","PROCESS","PROCESSLIST","PURGE","QUICK","RAID0","RAID_CHUNKS","RAID_CHUNKSIZE","RAID_TYPE","RANGE","READ","READ_ONLY","READ_WRITE","REFERENCES","REGEXP","RELOAD","RENAME","REPAIR","REPEATABLE","REPLACE","REPLICATION","RESET","RESTORE","RESTRICT","RETURN","RETURNS","REVOKE","RLIKE","ROLLBACK","ROW","ROWS","ROW_FORMAT","SECOND","SECURITY","SEPARATOR","SERIALIZABLE","SESSION","SHARE","SHOW","SHUTDOWN","SLAVE","SONAME","SOUNDS","SQL","SQL_AUTO_IS_NULL","SQL_BIG_RESULT","SQL_BIG_SELECTS","SQL_BIG_TABLES","SQL_BUFFER_RESULT","SQL_CACHE","SQL_CALC_FOUND_ROWS","SQL_LOG_BIN","SQL_LOG_OFF","SQL_LOG_UPDATE","SQL_LOW_PRIORITY_UPDATES","SQL_MAX_JOIN_SIZE","SQL_NO_CACHE","SQL_QUOTE_SHOW_CREATE","SQL_SAFE_UPDATES","SQL_SELECT_LIMIT","SQL_SLAVE_SKIP_COUNTER","SQL_SMALL_RESULT","SQL_WARNINGS","START","STARTING","STATUS","STOP","STORAGE","STRAIGHT_JOIN","STRING","STRIPED","SUPER","TABLE","TABLES","TEMPORARY","TERMINATED","THEN","TO","TRAILING","TRANSACTIONAL","TRIGGER","TRUE","TRUNCATE","TYPE","TYPES","UNCOMMITTED","UNIQUE","UNLOCK","UNSIGNED","USAGE","USE","USING","VARIABLES","VIEW","WHEN","WITH","WORK","WRITE","YEAR_MONTH"],qe=["ADD","AFTER","ALTER COLUMN","ALTER TABLE","CREATE OR REPLACE","DECLARE","DELETE FROM","EXCEPT","FETCH FIRST","FROM","GO","GRANT","GROUP BY","HAVING","INSERT INTO","INSERT","LIMIT","MODIFY","ORDER BY","RETURNING","SELECT","SET CURRENT SCHEMA","SET SCHEMA","SET","UPDATE","VALUES","WHERE"],eE=["INTERSECT ALL","INTERSECT","MINUS","UNION ALL","UNION"],EE=["AND","CROSS APPLY","CROSS JOIN","ELSE","INNER JOIN","FULL JOIN","FULL OUTER JOIN","LEFT JOIN","LEFT OUTER JOIN","NATURAL JOIN","OR","OUTER APPLY","OUTER JOIN","RENAME","RIGHT JOIN","RIGHT OUTER JOIN","JOIN","WHEN","XOR"]});var Z=o(A=>{"use strict";var U=A&&A.__importDefault||function(E){return E&&E.__esModule?E:{default:E}};A.__esModule=!0;A.tokenize=A.format=void 0;var tE=U(k()),rE=U(K()),TE=U(x()),J=U(Q()),RE=function(E,e){switch(e===void 0&&(e={}),e.language){case"db2":return new tE.default(e).format(E);case"n1ql":return new rE.default(e).format(E);case"pl/sql":return new TE.default(e).format(E);default:return new J.default(e).format(E)}};A.format=RE;var nE=function(E,e){return e===void 0&&(e={}),new J.default(e).tokenize(E)};A.tokenize=nE;A.default={format:A.format,tokenize:A.tokenize}});var z=te(Z(),1);globalThis.phpdebugbar_sqlformatter=z.default.default;})();

window.PhpDebugBar = window.PhpDebugBar || {};

(function () {
    const PhpDebugBar = window.PhpDebugBar;
    PhpDebugBar.utils = PhpDebugBar.utils || {};

    /**
     * Returns the value from an object property.
     * Using dots in the key, it is possible to retrieve nested property values.
     *
     * Note: This returns `defaultValue` only when the path is missing (null/undefined),
     * not when the value is falsy (0/false/"").
     *
     * @param {Record<string, any>} dict
     * @param {string} key
     * @param {any} [defaultValue]
     * @returns {any}
     */
    const getDictValue = PhpDebugBar.utils.getDictValue = function (dict, key, defaultValue) {
        if (dict === null || dict === undefined) {
            return defaultValue;
        }

        const parts = String(key).split('.');
        let d = dict;

        for (const part of parts) {
            if (d === null || d === undefined) {
                return defaultValue;
            }
            d = d[part];
            if (d === undefined) {
                return defaultValue;
            }
        }

        return d;
    };

    /**
     * Returns a prefixed CSS class name (or selector).
     *
     * If `cls` contains spaces, each class is prefixed.
     * If `cls` starts with ".", the dot is preserved (selector form).
     *
     * @param {string} cls
     * @param {string} prefix
     * @returns {string}
     */
    PhpDebugBar.utils.csscls = function (cls, prefix) {
        const s = String(cls).trim();

        if (s.includes(' ')) {
            return s
                .split(/\s+/)
                .filter(Boolean)
                .map(c => PhpDebugBar.utils.csscls(c, prefix))
                .join(' ');
        }

        if (s.startsWith('.')) {
            return `.${prefix}${s.slice(1)}`;
        }

        return prefix + s;
    };

    /**
     * Creates a partial function of csscls where the second
     * argument is already defined
     *
     * @param  {string} prefix
     * @return {Function}
     */
    PhpDebugBar.utils.makecsscls = function (prefix) {
        return cls => PhpDebugBar.utils.csscls(cls, prefix);
    };

    const csscls = PhpDebugBar.utils.makecsscls('phpdebugbar-');

    PhpDebugBar.utils.sfDump = function (el) {
        if (typeof window.Sfdump == 'function') {
            el.querySelectorAll('pre.sf-dump[id]').forEach((pre) => {
                window.Sfdump(pre.id, { maxDepth: 0 });
            });
        }
    };

    PhpDebugBar.utils.schedule = function (cb) {
        if (window.requestIdleCallback) {
            return window.requestIdleCallback(cb, { timeout: 1000 });
        }

        return setTimeout(cb, 0);
    };

    // ------------------------------------------------------------------

    /**
     * Base class for all elements with a visual component
     */
    class Widget {
        get tagName() {
            return 'div';
        }

        constructor(options = {}) {
            this._attributes = { ...this.defaults };
            this._boundAttributes = {};
            this.el = document.createElement(this.tagName);
            if (this.className) {
                this.el.classList.add(...this.className.split(' '));
            }
            this.initialize(options);
            this.render();
        }

        /**
         * Called after the constructor
         *
         * @param {object} options
         */
        initialize(options) {
            this.set(options);
        }

        /**
         * Called after the constructor to render the element
         */
        render() {}

        /**
         * Sets the value of an attribute
         *
         * @param {string | object} attr Attribute name or object with multiple attributes
         * @param {*} [value] Attribute value (optional if attr is an object)
         */
        set(attr, value) {
            const attrs = typeof attr === 'string' ? { [attr]: value } : attr;

            const callbacks = [];
            for (const attr in attrs) {
                value = attrs[attr];
                this._attributes[attr] = value;
                if (this._boundAttributes[attr]) {
                    for (const callback of this._boundAttributes[attr]) {
                        // Make sure to run the callback only once per attribute change
                        if (!callbacks.includes(callback)) {
                            callback.call(this, value);
                            callbacks.push(callback);
                        }
                    }
                }
            }
        }

        /**
         * Checks if an attribute exists and is not null
         *
         * @param {string} attr
         * @return {boolean}
         */
        has(attr) {
            return this._attributes[attr] !== undefined && this._attributes[attr] !== null;
        }

        /**
         * Returns the value of an attribute
         *
         * @param {string} attr
         * @return {*}
         */
        get(attr) {
            return this._attributes[attr];
        }

        /**
         * Registers a callback function that will be called whenever the value of the attribute changes
         *
         * If cb is a HTMLElement element, textContent will be used to fill the element
         *
         * @param {string | Array} attr
         * @param {Function | HTMLElement} cb
         */
        bindAttr(attr, cb) {
            if (Array.isArray(attr)) {
                for (const a of attr) {
                    this.bindAttr(a, cb);
                }
                return;
            }

            if (!this._boundAttributes[attr]) {
                this._boundAttributes[attr] = [];
            }
            if (cb instanceof HTMLElement) {
                const el = cb;
                cb = value => el.textContent = value || '';
            }
            this._boundAttributes[attr].push(cb);
            if (this.has(attr)) {
                cb.call(this, this._attributes[attr]);
            }
        }

        /**
         * Creates a subclass
         *
         * Code from Backbone.js
         *
         * @param {object} props Prototype properties
         * @return {Function}
         */
        static extend(props) {
            const Parent = this;
            class Child extends Parent {}

            // Use defineProperties to handle getters/setters properly
            for (const key in props) {
                const descriptor = Object.getOwnPropertyDescriptor(props, key);
                if (descriptor) {
                    Object.defineProperty(Child.prototype, key, descriptor);
                }
            }
            Object.assign(Child, Parent);
            Child.__super__ = Parent.prototype;

            return Child;
        }
    }
    Widget.prototype.defaults = {};

    PhpDebugBar.Widget = Widget;

    // ------------------------------------------------------------------

    /**
     * Tab
     *
     * A tab is composed of a tab label which is always visible and
     * a tab panel which is visible only when the tab is active.
     *
     * The panel must contain a widget. A widget is an object which has
     * an element property containing something appendable to a HTMLElement object.
     *
     * Options:
     *  - title
     *  - badge
     *  - widget
     *  - data: forward data to widget data
     */
    class Tab extends Widget {
        get className() {
            return csscls('panel');
        }

        render() {
            this.active = false;
            this.tab = document.createElement('a');
            this.tab.classList.add(csscls('tab'));

            this.icon = document.createElement('i');
            this.tab.append(this.icon);
            this.bindAttr('icon', function (icon) {
                if (icon) {
                    this.icon.className = `phpdebugbar-icon phpdebugbar-icon-${icon}`;
                } else {
                    this.icon.className = '';
                }
            });

            const title = document.createElement('span');
            title.classList.add(csscls('text'));
            this.tab.append(title);
            this.bindAttr('title', title);

            this.badge = document.createElement('span');
            this.badge.classList.add(csscls('badge'));
            this.tab.append(this.badge);

            this.bindAttr('badge', function (value) {
                if (value !== null) {
                    this.badge.textContent = value;
                    this.badge.classList.add(csscls('visible'));
                } else {
                    this.badge.classList.remove(csscls('visible'));
                }
            });

            this.bindAttr('widget', function (widget) {
                this.el.innerHTML = '';
                this.el.append(widget.el);
            });

            this.widgetRendered = false;
            this.bindAttr('data', function (data) {
                if (this.has('widget')) {
                    this.tab.setAttribute('data-empty', Object.keys(data).length === 0 || data.count === 0);
                    if (!this.widgetRendered && this.active && data != null) {
                        this.renderWidgetData();
                    } else {
                        this.widgetRendered = false;
                    }
                }
            });
        }

        renderWidgetData() {
            const data = this.get('data');
            const widget = this.get('widget');
            if (data == null || !widget) {
                return;
            }

            widget.set('data', data);
            PhpDebugBar.utils.schedule(() => {
                PhpDebugBar.utils.sfDump(widget.el);
            });

            this.widgetRendered = true;
        }

        show() {
            const activeClass = csscls('active');
            this.tab.classList.add(activeClass);
            this.tab.hidden = false;
            this.el.classList.add(activeClass);
            this.el.hidden = false;
            this.active = true;

            if (!this.widgetRendered) {
                this.renderWidgetData();
            }
        }

        hide() {
            const activeClass = csscls('active');
            this.tab.classList.remove(activeClass);
            this.el.classList.remove(activeClass);
            this.el.hidden = true;
            this.active = false;
        }
    }

    // ------------------------------------------------------------------

    /**
     * Indicator
     *
     * An indicator is a text and an icon to display single value information
     * right inside the always visible part of the debug bar
     *
     * Options:
     *  - icon
     *  - title
     *  - tooltip
     *  - data: alias of title
     */
    class Indicator extends Widget {
        get tagName() {
            return 'span';
        }

        get className() {
            return csscls('indicator');
        }

        render() {
            this.icon = document.createElement('i');
            this.el.append(this.icon);
            this.bindAttr('icon', function (icon) {
                if (icon) {
                    this.icon.className = `phpdebugbar-icon phpdebugbar-icon-${icon}`;
                } else {
                    this.icon.className = '';
                }
            });

            this.bindAttr('link', function (link) {
                if (link) {
                    this.el.addEventListener('click', () => {
                        this.get('debugbar').showTab(link);
                    });
                    this.el.style.cursor = 'pointer';
                } else {
                    this.el.style.cursor = '';
                }
            });

            const textSpan = document.createElement('span');
            textSpan.classList.add(csscls('text'));
            this.el.append(textSpan);
            this.bindAttr(['title', 'data'], textSpan);

            this.tooltip = document.createElement('span');
            this.tooltip.classList.add(csscls('tooltip'), csscls('disabled'));
            this.el.append(this.tooltip);
            this.bindAttr('tooltip', function (tooltip) {
                if (tooltip) {
                    if (Array.isArray(tooltip) || typeof tooltip === 'object') {
                        const dl = document.createElement('dl');
                        for (const [key, value] of Object.entries(tooltip)) {
                            const dt = document.createElement('dt');
                            dt.textContent = key;
                            dl.append(dt);

                            const dd = document.createElement('dd');
                            dd.textContent = value;
                            dl.append(dd);
                        }
                        this.tooltip.innerHTML = '';
                        this.tooltip.append(dl);
                        this.tooltip.classList.remove(csscls('disabled'));
                    } else {
                        this.tooltip.textContent = tooltip;
                        this.tooltip.classList.remove(csscls('disabled'));
                    }
                } else {
                    this.tooltip.classList.add(csscls('disabled'));
                }
            });
        }
    }

    /**
     * Displays datasets in a table
     *
     */
    class Settings extends Widget {
        get tagName() {
            return 'form';
        }

        get className() {
            return csscls('settings');
        }

        initialize(options) {
            this.set(options);

            const debugbar = this.get('debugbar');
            this.settings = JSON.parse(localStorage.getItem('phpdebugbar-settings')) || {};

            for (const key in debugbar.options) {
                if (key in this.settings) {
                    debugbar.options[key] = this.settings[key];
                }

                // Theme requires dark/light mode detection
                if (key === 'theme') {
                    debugbar.setTheme(debugbar.options[key]);
                } else {
                    debugbar.el.setAttribute(`data-${key}`, debugbar.options[key]);
                }
            }
        }

        clearSettings() {
            const debugbar = this.get('debugbar');

            // Remove item from storage
            localStorage.removeItem('phpdebugbar-settings');
            localStorage.removeItem('phpdebugbar-ajaxhandler-autoshow');
            this.settings = {};

            // Reset options
            debugbar.options = { ...debugbar.defaultOptions };

            // Reset ajax handler
            if (debugbar.ajaxHandler) {
                const autoshow = debugbar.ajaxHandler.defaultAutoShow;
                debugbar.ajaxHandler.setAutoShow(autoshow);
                this.set('autoshow', autoshow);
                if (debugbar.controls.__datasets) {
                    debugbar.controls.__datasets.get('widget').set('autoshow', this.autoshow.checked);
                }
            }

            this.initialize(debugbar.options);
        }

        storeSetting(key, value) {
            this.settings[key] = value;

            const debugbar = this.get('debugbar');
            debugbar.options[key] = value;
            if (key !== 'theme') {
                debugbar.el.setAttribute(`data-${key}`, value);
            }

            localStorage.setItem('phpdebugbar-settings', JSON.stringify(this.settings));
        }

        render() {
            this.el.innerHTML = '';

            const debugbar = this.get('debugbar');
            const self = this;

            const fields = {};

            // Set Theme
            const themeSelect = document.createElement('select');
            themeSelect.innerHTML = '<option value="auto">Auto (System preference)</option>'
                + '<option value="light">Light</option>'
                + '<option value="dark">Dark</option>';
            themeSelect.value = debugbar.options.theme;
            themeSelect.addEventListener('change', function () {
                self.storeSetting('theme', this.value);
                debugbar.setTheme(this.value);
            });
            fields.Theme = themeSelect;

            // Open Button Position
            const positionSelect = document.createElement('select');
            positionSelect.innerHTML = '<option value="bottomLeft">Bottom Left</option>'
                + '<option value="bottomRight">Bottom Right</option>'
                + '<option value="topLeft">Top Left</option>'
                + '<option value="topRight">Top Right</option>';
            positionSelect.value = debugbar.options.openBtnPosition;
            positionSelect.addEventListener('change', function () {
                self.storeSetting('openBtnPosition', this.value);
                if (this.value === 'topLeft' || this.value === 'topRight') {
                    self.storeSetting('toolbarPosition', 'top');
                } else {
                    self.storeSetting('toolbarPosition', 'bottom');
                }
                self.get('debugbar').recomputeBottomOffset();
            });
            fields['Toolbar Position'] = positionSelect;

            // Hide Empty Tabs
            this.hideEmptyTabs = document.createElement('input');
            this.hideEmptyTabs.type = 'checkbox';
            this.hideEmptyTabs.checked = debugbar.options.hideEmptyTabs;
            this.hideEmptyTabs.addEventListener('click', function () {
                self.storeSetting('hideEmptyTabs', this.checked);
                // Reset button size
                self.get('debugbar').respCSSSize = 0;
                self.get('debugbar').resize();
            });

            const hideEmptyTabsLabel = document.createElement('label');
            hideEmptyTabsLabel.append(this.hideEmptyTabs, 'Hide empty tabs until they have data');
            fields['Hide Empty Tabs'] = hideEmptyTabsLabel;

            // Autoshow
            this.autoshow = document.createElement('input');
            this.autoshow.type = 'checkbox';
            this.autoshow.checked = debugbar.ajaxHandler && debugbar.ajaxHandler.autoShow;
            this.autoshow.addEventListener('click', function () {
                if (debugbar.ajaxHandler) {
                    debugbar.ajaxHandler.setAutoShow(this.checked);
                }
                if (debugbar.controls.__datasets) {
                    debugbar.controls.__datasets.get('widget').set('autoshow', this.checked);
                }
                // Update dataset switcher widget
                if (debugbar.datasetSwitcherWidget) {
                    debugbar.datasetSwitcherWidget.set('autoshow', this.checked);
                }
            });

            this.bindAttr('autoshow', function () {
                this.autoshow.checked = this.get('autoshow');
                const row = this.autoshow.closest(`.${csscls('form-row')}`);
                if (row) {
                    row.style.display = '';
                }
            });

            const autoshowLabel = document.createElement('label');
            autoshowLabel.append(this.autoshow, 'Automatically show new incoming Ajax requests');
            fields.Autoshow = autoshowLabel;

            // Reset button
            const resetButton = document.createElement('button');
            resetButton.textContent = 'Reset settings';
            resetButton.addEventListener('click', (e) => {
                e.preventDefault();
                self.clearSettings();
                self.render();
            });
            fields['Reset to defaults'] = resetButton;

            for (const [key, value] of Object.entries(fields)) {
                const formRow = document.createElement('div');
                formRow.classList.add(csscls('form-row'));

                const formLabel = document.createElement('div');
                formLabel.classList.add(csscls('form-label'));
                formLabel.textContent = key;
                formRow.append(formLabel);

                const formInput = document.createElement('div');
                formInput.classList.add(csscls('form-input'));
                if (value instanceof HTMLElement) {
                    formInput.append(value);
                } else {
                    formInput.innerHTML = value;
                }
                formRow.append(formInput);

                self.el.append(formRow);
            }

            if (!debugbar.ajaxHandler) {
                this.autoshow.closest(`.${csscls('form-row')}`).style.display = 'none';
            }
        }
    }

    // ------------------------------------------------------------------

    /**
     * Dataset title formater
     *
     * Formats the title of a dataset for the select box
     */
    class DatasetTitleFormater {
        constructor(debugbar) {
            this.debugbar = debugbar;
        }

        /**
         * Formats the title of a dataset
         *
         * @param {string} id
         * @param {object} data
         * @param {string} suffix
         * @param {number} nb
         * @return {string}
         */
        format(id, data, suffix, nb) {
            suffix = suffix ? ` ${suffix}` : '';
            nb = nb || Object.keys(this.debugbar.datasets).length;

            if (data.__meta === undefined) {
                return `#${nb}${suffix}`;
            }

            const uri = data.__meta.uri.split('/');
            let filename = uri.pop();

            // URI ends in a trailing /, avoid returning an empty string
            if (!filename) {
                filename = `${uri.pop() || ''}/`; // add the trailing '/' back
            }

            // filename is a number, path could be like /action/{id}
            if (uri.length && !Number.isNaN(filename)) {
                filename = `${uri.pop()}/${filename}`;
            }

            // truncate the filename in the label, if it's too long
            const maxLength = 150;
            if (filename.length > maxLength) {
                filename = `${filename.substr(0, maxLength)}...`;
            }

            const label = `#${nb} ${filename}${suffix} (${data.__meta.datetime.split(' ')[1]})`;
            return label;
        }
    }

    PhpDebugBar.DatasetTitleFormater = DatasetTitleFormater;

    // ------------------------------------------------------------------

    /**
     * DebugBar
     *
     * Creates a bar that appends itself to the body of your page
     * and sticks to the bottom.
     *
     * The bar can be customized by adding tabs and indicators.
     * A data map is used to fill those controls with data provided
     * from datasets.
     */
    class DebugBar extends Widget {
        get className() {
            return `phpdebugbar`;
        }

        initialize(options = {}) {
            this.options = Object.assign({
                bodyBottomInset: true,
                theme: 'auto',
                toolbarPosition: 'bottom',
                openBtnPosition: 'bottomLeft',
                hideEmptyTabs: false,
                spaNavigationEvents: []
            }, options);
            this.defaultOptions = { ...this.options };
            this.controls = {};
            this.dataMap = {};
            this.datasets = {};
            this.firstTabName = null;
            this.activePanelName = null;
            this.activeDatasetId = null;
            this.pendingDataSetId = null;
            this.datesetTitleFormater = new DatasetTitleFormater(this);
            const bodyStyles = window.getComputedStyle(document.body);
            this.bodyPaddingBottomHeight = Number.parseInt(bodyStyles.paddingBottom);
            this.bodyPaddingTopHeight = Number.parseInt(bodyStyles.paddingTop);

            try {
                this.isIframe = window.self !== window.top && window.top.PhpDebugBar && window.top.PhpDebugBar;
            } catch (_error) {
                this.isIframe = false;
            }
            this.registerResizeHandler();
            this.registerMediaListener();
            this.registerNavigationListener();

            // Attach settings
            this.settingsControl = new PhpDebugBar.DebugBar.Tab({ icon: 'adjustments-horizontal', title: 'Settings', widget: new Settings({
                debugbar: this
            }) });
        }

        /**
         * Register resize event, for resize debugbar with reponsive css.
         *
         * @this {DebugBar}
         */
        registerResizeHandler() {
            if (this.resize.bind === undefined || this.isIframe) {
                return;
            }

            const f = this.resize.bind(this);
            this.respCSSSize = 0;
            window.addEventListener('resize', f);
            setTimeout(f, 20);
        }

        registerMediaListener() {
            const mediaQueryList = window.matchMedia('(prefers-color-scheme: dark)');
            mediaQueryList.addEventListener('change', (event) => {
                if (this.options.theme === 'auto') {
                    this.setTheme('auto');
                }
            });
        }

        /**
         * Register navigation event listeners for SPA frameworks.
         *
         * Listens for events configured via the `spaNavigationEvents` option
         * and recalculates body padding after navigation completes.
         */
        registerNavigationListener() {
            const events = this.options.spaNavigationEvents;
            if (!events || !events.length) {
                return;
            }

            for (const eventName of events) {
                document.addEventListener(eventName, () => {
                    this.recalculateBodyPadding();
                });
            }
        }

        /**
         * Recalculates and caches the body's original padding values.
         */
        recalculateBodyPadding() {
            if (!this.options.bodyBottomInset) {
                return;
            }

            // Clear inline styles to read the page's actual CSS values
            document.body.style.paddingTop = '';
            document.body.style.paddingBottom = '';

            // Read the new page's padding values
            const bodyStyles = window.getComputedStyle(document.body);
            this.bodyPaddingTopHeight = Number.parseFloat(bodyStyles.paddingTop);
            this.bodyPaddingBottomHeight = Number.parseFloat(bodyStyles.paddingBottom);

            // Reapply the debugbar offset with the new values
            this.recomputeBottomOffset();
        }

        setTheme(theme) {
            this.options.theme = theme;

            if (theme === 'auto') {
                const mediaQueryList = window.matchMedia('(prefers-color-scheme: dark)');
                theme = mediaQueryList.matches ? 'dark' : 'light';
            }

            this.el.setAttribute('data-theme', theme);
            if (this.openHandler) {
                this.openHandler.el.setAttribute('data-theme', theme);
            }
            if (this.datasetSwitcherWidget && this.datasetSwitcherWidget.panel) {
                this.datasetSwitcherWidget.panel.setAttribute('data-theme', theme);
            }
        }

        /**
         * Resizes the debugbar to fit the current browser window
         */
        resize() {
            if (this.isIframe) {
                return;
            }

            let contentSize = this.respCSSSize;
            if (this.respCSSSize === 0) {
                const visibleChildren = Array.from(this.header.children).filter((el) => {
                    return el.offsetParent !== null;
                });
                for (const child of visibleChildren) {
                    const styles = window.getComputedStyle(child);
                    contentSize += child.offsetWidth
                        + Number.parseFloat(styles.marginLeft)
                        + Number.parseFloat(styles.marginRight);
                }
            }

            const currentSize = this.header.offsetWidth;
            const cssClass = csscls('mini-design');
            const bool = this.header.classList.contains(cssClass);

            if (currentSize <= contentSize && !bool) {
                this.respCSSSize = contentSize;
                this.header.classList.add(cssClass);
            } else if (contentSize < currentSize && bool) {
                this.respCSSSize = 0;
                this.header.classList.remove(cssClass);
            }

            // Reset height to ensure bar is still visible
            const currentHeight = this.body.clientHeight || Number.parseInt(localStorage.getItem('phpdebugbar-height'), 10) || 300;
            this.setHeight(currentHeight);
        }

        /**
         * Initialiazes the UI
         *
         * @this {DebugBar}
         */
        render() {
            if (this.isIframe) {
                this.el.hidden = true;
            }

            const self = this;
            document.body.append(this.el);

            this.dragCapture = document.createElement('div');
            this.dragCapture.classList.add(csscls('drag-capture'));
            this.el.append(this.dragCapture);

            this.resizeHandle = document.createElement('div');
            this.resizeHandle.classList.add(csscls('resize-handle'));
            this.resizeHandle.classList.add(csscls('resize-handle-top'));
            this.el.append(this.resizeHandle);

            this.header = document.createElement('div');
            this.header.classList.add(csscls('header'));
            this.el.append(this.header);

            this.headerBtn = document.createElement('a');
            this.headerBtn.classList.add(csscls('restore-btn'));
            this.header.append(this.headerBtn);
            this.headerBtn.addEventListener('click', () => {
                self.close();
            });

            this.headerLeft = document.createElement('div');
            this.headerLeft.classList.add(csscls('header-left'));
            this.header.append(this.headerLeft);

            this.headerRight = document.createElement('div');
            this.headerRight.classList.add(csscls('header-right'));
            this.header.append(this.headerRight);

            this.body = document.createElement('div');
            this.body.classList.add(csscls('body'));
            this.el.append(this.body);
            this.recomputeBottomOffset();

            this.resizeHandleBottom = document.createElement('div');
            this.resizeHandleBottom.classList.add(csscls('resize-handle'));
            this.resizeHandleBottom.classList.add(csscls('resize-handle-bottom'));
            this.el.append(this.resizeHandleBottom);

            // dragging of resize handle
            let pos_y, orig_h;
            const mousemove = (e) => {
                const h = orig_h + (pos_y - e.pageY);
                self.setHeight(h);
            };
            const mousemoveBottom = (e) => {
                const h = orig_h - (pos_y - e.pageY);
                self.setHeight(h);
            };
            const mouseup = () => {
                document.removeEventListener('mousemove', mousemove);
                document.removeEventListener('mousemove', mousemoveBottom);
                document.removeEventListener('mouseup', mouseup);
                self.dragCapture.style.display = 'none';
            };
            this.resizeHandle.addEventListener('mousedown', (e) => {
                orig_h = self.body.offsetHeight;
                pos_y = e.pageY;
                document.addEventListener('mousemove', mousemove);
                document.addEventListener('mouseup', mouseup);
                self.dragCapture.style.display = '';
                e.preventDefault();
            });
            this.resizeHandleBottom.addEventListener('mousedown', (e) => {
                orig_h = self.body.offsetHeight;
                pos_y = e.pageY;
                document.addEventListener('mousemove', mousemoveBottom);
                document.addEventListener('mouseup', mouseup);
                self.dragCapture.style.display = '';
                e.preventDefault();
            });

            // close button
            this.closebtn = document.createElement('a');
            this.closebtn.classList.add(csscls('close-btn'));
            this.headerRight.append(this.closebtn);
            this.closebtn.addEventListener('click', () => {
                self.close();
            });

            // minimize button
            this.minimizebtn = document.createElement('a');
            this.minimizebtn.classList.add(csscls('minimize-btn'));
            this.minimizebtn.hidden = !this.isMinimized();
            this.headerRight.append(this.minimizebtn);
            this.minimizebtn.addEventListener('click', () => {
                self.minimize();
            });

            // maximize button
            this.maximizebtn = document.createElement('a');
            this.maximizebtn.classList.add(csscls('maximize-btn'));
            this.maximizebtn.hidden = this.isMinimized();
            this.headerRight.append(this.maximizebtn);
            this.maximizebtn.addEventListener('click', () => {
                self.restore();
            });

            // restore button
            this.restorebtn = document.createElement('a');
            this.restorebtn.classList.add(csscls('restore-btn'));
            this.restorebtn.hidden = true;
            this.el.append(this.restorebtn);
            this.restorebtn.addEventListener('click', () => {
                self.restore();
            });

            // open button
            this.openbtn = document.createElement('a');
            this.openbtn.classList.add(csscls('open-btn'));
            this.openbtn.hidden = true;
            this.headerRight.append(this.openbtn);
            this.openbtn.addEventListener('click', () => {
                self.openHandler.show((id, dataset) => {
                    self.addDataSet(dataset, id, '(opened)');
                });
            });

            // select box for data sets (only if AJAX handler is not used)
            this.datasetsSelectSpan = document.createElement('span');
            this.datasetsSelectSpan.classList.add(csscls('datasets-switcher'));
            this.datasetsSelectSpan.setAttribute('name', 'datasets-switcher');
            this.datasetsSelect = document.createElement('select');
            this.datasetsSelect.hidden = true;

            this.datasetsSelectSpan.append(this.datasetsSelect);

            this.headerRight.append(this.datasetsSelectSpan);
            this.datasetsSelect.addEventListener('change', function () {
                self.showDataSet(this.value);
            });

            this.controls.__settings = this.settingsControl;
            this.settingsControl.tab.classList.add(csscls('tab-settings'));
            this.settingsControl.tab.setAttribute('data-collector', '__settings');
            this.settingsControl.el.setAttribute('data-collector', '__settings');
            this.settingsControl.el.hidden = true;

            this.maximizebtn.after(this.settingsControl.tab);
            this.settingsControl.tab.hidden = false;
            this.settingsControl.tab.addEventListener('click', () => {
                if (!this.isMinimized() && this.activePanelName === '__settings') {
                    this.minimize();
                } else {
                    this.showTab('__settings');
                    this.settingsControl.get('widget').render();
                }
            });
            this.body.append(this.settingsControl.el);
        }

        /**
         * Sets the height of the debugbar body section
         * Forces the height to lie within a reasonable range
         * Stores the height in local storage so it can be restored
         * Resets the document body bottom offset
         *
         * @this {DebugBar}
         */
        setHeight(height) {
            const min_h = 40;
            const max_h = window.innerHeight - this.header.offsetHeight - 10;
            height = Math.min(height, max_h);
            height = Math.max(height, min_h);
            this.body.style.height = `${height}px`;
            localStorage.setItem('phpdebugbar-height', height);
            this.recomputeBottomOffset();
        }

        /**
         * Restores the state of the DebugBar using localStorage
         * This is not called by default in the constructor and
         * needs to be called by subclasses in their init() method
         *
         * @this {DebugBar}
         */
        restoreState() {
            if (this.isIframe) {
                return;
            }
            // bar height
            const height = localStorage.getItem('phpdebugbar-height');
            this.setHeight(Number.parseInt(height) || this.body.offsetHeight);

            // bar visibility
            const open = localStorage.getItem('phpdebugbar-open');
            if (open && open === '0') {
                this.close();
            } else {
                const visible = localStorage.getItem('phpdebugbar-visible');
                if (visible && visible === '1') {
                    const tab = localStorage.getItem('phpdebugbar-tab');
                    if (this.isTab(tab)) {
                        this.showTab(tab);
                    } else {
                        this.showTab();
                    }
                } else {
                    this.minimize();
                }
            }
        }

        /**
         * Creates and adds a new tab
         *
         * @this {DebugBar}
         * @param {string} name Internal name
         * @param {object} widget A widget object with an element property
         * @param {string} title The text in the tab, if not specified, name will be used
         * @return {Tab}
         */
        createTab(name, widget, title) {
            const tab = new Tab({
                title: title || (name.replace(/[_-]/g, ' ').charAt(0).toUpperCase() + name.slice(1)),
                widget
            });
            return this.addTab(name, tab);
        }

        /**
         * Adds a new tab
         *
         * @this {DebugBar}
         * @param {string} name Internal name
         * @param {Tab} tab Tab object
         * @return {Tab}
         */
        addTab(name, tab) {
            if (this.isControl(name)) {
                throw new Error(`${name} already exists`);
            }

            const self = this;
            this.headerLeft.append(tab.tab);
            tab.tab.addEventListener('click', () => {
                if (!self.isMinimized() && self.activePanelName === name) {
                    self.minimize();
                } else {
                    self.restore();
                    self.showTab(name);
                }
            });
            tab.tab.setAttribute('data-empty', true);
            tab.tab.setAttribute('data-collector', name);
            tab.el.setAttribute('data-collector', name);
            this.body.append(tab.el);

            this.controls[name] = tab;
            if (this.firstTabName === null) {
                this.firstTabName = name;
            }
            return tab;
        }

        /**
         * Creates and adds an indicator
         *
         * @this {DebugBar}
         * @param {string} name Internal name
         * @param {string} icon
         * @param {string | object} tooltip
         * @param {string} position "right" or "left", default is "right"
         * @return {Indicator}
         */
        createIndicator(name, icon, tooltip, position) {
            const indicator = new Indicator({
                icon,
                tooltip
            });
            return this.addIndicator(name, indicator, position);
        }

        /**
         * Adds an indicator
         *
         * @this {DebugBar}
         * @param {string} name Internal name
         * @param {Indicator} indicator Indicator object
         * @return {Indicator}
         */
        addIndicator(name, indicator, position) {
            if (this.isControl(name)) {
                throw new Error(`${name} already exists`);
            }

            indicator.set('debugbar', this);

            if (position === 'left') {
                this.headerLeft.prepend(indicator.el);
            } else {
                this.headerRight.append(indicator.el);
            }

            this.controls[name] = indicator;
            return indicator;
        }

        /**
         * Returns a control
         *
         * @param {string} name
         * @return {object}
         */
        getControl(name) {
            if (this.isControl(name)) {
                return this.controls[name];
            }
        }

        /**
         * Checks if there's a control under the specified name
         *
         * @this {DebugBar}
         * @param {string} name
         * @return {boolean}
         */
        isControl(name) {
            return this.controls[name] !== undefined;
        }

        /**
         * Checks if a tab with the specified name exists
         *
         * @this {DebugBar}
         * @param {string} name
         * @return {boolean}
         */
        isTab(name) {
            return this.isControl(name) && this.controls[name] instanceof Tab;
        }

        /**
         * Checks if an indicator with the specified name exists
         *
         * @this {DebugBar}
         * @param {string} name
         * @return {boolean}
         */
        isIndicator(name) {
            return this.isControl(name) && this.controls[name] instanceof Indicator;
        }

        /**
         * Removes all tabs and indicators from the debug bar and hides it
         *
         * @this {DebugBar}
         */
        reset() {
            this.minimize();
            for (const [name, control] of Object.entries(this.controls)) {
                if (this.isTab(name)) {
                    control.tab.remove();
                }
                control.el.remove();
            }
            this.controls = {};
        }

        /**
         * Open the debug bar and display the specified tab
         *
         * @this {DebugBar}
         * @param {string} name If not specified, display the first tab
         */
        showTab(name) {
            if (!name) {
                if (this.activePanelName) {
                    name = this.activePanelName;
                } else {
                    name = this.firstTabName;
                }
            }

            if (!this.isTab(name)) {
                throw new Error(`Unknown tab '${name}'`);
            }

            this.body.hidden = false;

            this.recomputeBottomOffset();

            for (const [controleName, control] of Object.entries(this.controls)) {
                if (control instanceof Tab) {
                    if (controleName === name) {
                        control.show();
                    } else {
                        control.hide();
                    }
                }
            }

            this.activePanelName = name;

            this.el.classList.remove(csscls('minimized'));
            localStorage.setItem('phpdebugbar-visible', '1');
            localStorage.setItem('phpdebugbar-tab', name);

            this.maximize();
        }

        /**
         * Hide panels and minimize the debug bar
         *
         * @this {DebugBar}
         */
        minimize() {
            const activeClass = csscls('active');
            const headerActives = this.header.querySelectorAll(`:scope > div > .${activeClass}`);
            for (const el of headerActives) {
                el.classList.remove(activeClass);
            }
            this.body.hidden = true;
            this.minimizebtn.hidden = true;
            this.maximizebtn.hidden = false;

            this.recomputeBottomOffset();
            localStorage.setItem('phpdebugbar-visible', '0');
            this.el.classList.add(csscls('minimized'));
            this.resize();
        }

        /**
         * Show panels and maxime the debug bar
         *
         * @this {DebugBar}
         */
        maximize() {
            this.header.hidden = false;
            this.restorebtn.hidden = true;
            this.body.hidden = false;
            this.minimizebtn.hidden = false;
            this.maximizebtn.hidden = true;

            this.recomputeBottomOffset();
            localStorage.setItem('phpdebugbar-visible', '1');
            localStorage.setItem('phpdebugbar-open', '1');
            this.el.classList.remove(csscls('minimized'));
            this.el.classList.remove(csscls('closed'));

            this.resize();
        }

        /**
         * Checks if the panel is minimized
         *
         * @return {boolean}
         */
        isMinimized() {
            return this.el.classList.contains(csscls('minimized'));
        }

        /**
         * Close the debug bar
         *
         * @this {DebugBar}
         */
        close() {
            this.header.hidden = true;
            this.body.hidden = true;
            this.restorebtn.hidden = false;
            localStorage.setItem('phpdebugbar-open', '0');
            this.el.classList.add(csscls('closed'));
            this.recomputeBottomOffset();
        }

        /**
         * Checks if the panel is closed
         *
         * @return {boolean}
         */
        isClosed() {
            return this.el.classList.contains(csscls('closed'));
        }

        /**
         * Restore the debug bar
         *
         * @this {DebugBar}
         */
        restore() {
            const tab = localStorage.getItem('phpdebugbar-tab');
            if (this.pendingDataSetId) {
                this.dataChangeHandler(this.datasets[this.pendingDataSetId]);
                this.pendingDataSetId = null;
            }
            if (this.isTab(tab)) {
                this.showTab(tab);
            } else {
                this.showTab();
            }
        }

        /**
         * Recomputes the margin-bottom css property of the body so
         * that the debug bar never hides any content
         */
        recomputeBottomOffset() {
            if (this.options.bodyBottomInset) {
                if (this.isClosed()) {
                    document.body.style.paddingBottom = this.bodyPaddingBottomHeight ? `${this.bodyPaddingBottomHeight}px` : '';
                    document.body.style.paddingTop = this.bodyPaddingTopHeight ? `${this.bodyPaddingTopHeight}px` : '';
                    return;
                }

                if (this.options.toolbarPosition === 'top') {
                    const offset = this.el.offsetHeight + (this.bodyPaddingTopHeight || 0);
                    document.body.style.paddingTop = `${offset}px`;
                    document.body.style.paddingBottom = this.bodyPaddingBottomHeight ? `${this.bodyPaddingBottomHeight}px` : '';
                } else {
                    const offset = this.el.offsetHeight + (this.bodyPaddingBottomHeight || 0);
                    document.body.style.paddingBottom = `${offset}px`;
                    document.body.style.paddingTop = this.bodyPaddingTopHeight ? `${this.bodyPaddingTopHeight}px` : '';
                }
            }
        }

        /**
         * Sets the data map used by dataChangeHandler to populate
         * indicators and widgets
         *
         * A data map is an object where properties are control names.
         * The value of each property should be an array where the first
         * item is the name of a property from the data object (nested properties
         * can be specified) and the second item the default value.
         *
         * Example:
         *     {"memory": ["memory.peak_usage_str", "0B"]}
         *
         * @this {DebugBar}
         * @param {object} map
         */
        setDataMap(map) {
            this.dataMap = map;
        }

        /**
         * Same as setDataMap() but appends to the existing map
         * rather than replacing it
         *
         * @this {DebugBar}
         * @param {object} map
         */
        addDataMap(map) {
            Object.assign(this.dataMap, map);
        }

        /**
         * Resets datasets and add one set of data
         *
         * For this method to be usefull, you need to specify
         * a dataMap using setDataMap()
         *
         * @this {DebugBar}
         * @param {object} data
         * @return {string} Dataset's id
         */
        setData(data) {
            this.datasets = {};
            return this.addDataSet(data);
        }

        /**
         * Adds a dataset
         *
         * If more than one dataset are added, the dataset selector
         * will be displayed.
         *
         * For this method to be usefull, you need to specify
         * a dataMap using setDataMap()
         *
         * @this {DebugBar}
         * @param {object} data
         * @param {string} id The name of this set, optional
         * @param {string} suffix
         * @param {Bool} show Whether to show the new dataset, optional (default: true)
         * @return {string} Dataset's id
         */
        addDataSet(data, id, suffix, show) {
            if (!data || !data.__meta) {
                return;
            }
            if (this.isIframe && window.top.PhpDebugBar && window.top.PhpDebugBar.instance) {
                window.top.PhpDebugBar.instance.addDataSet(data, id, `(iframe)${suffix || ''}`, show);
                return;
            }

            const nb = Object.keys(this.datasets).length + 1;
            id = id || nb;
            data.__meta.nb = nb;
            data.__meta.suffix = suffix;
            this.datasets[id] = data;

            const label = this.datesetTitleFormater.format(id, this.datasets[id], suffix, nb);

            // Update dataset switcher widget (if AJAX handler is enabled)
            if (this.datasetSwitcherWidget) {
                this.datasetSwitcherWidget.set('data', this.datasets);
            } else {
                // Use old dropdown (if AJAX handler is not enabled)
                const option = document.createElement('option');
                option.value = id;
                option.textContent = label;
                this.datasetsSelect.append(option);
                this.datasetsSelect.hidden = false;
            }

            if (show === undefined || show) {
                this.showDataSet(id);
            }

            this.resize();

            return id;
        }

        /**
         * Loads a dataset using the open handler
         *
         * @param {string} id
         * @param {Bool} show Whether to show the new dataset, optional (default: true)
         */
        loadDataSet(id, suffix, callback, show) {
            if (!this.openHandler) {
                throw new Error('loadDataSet() needs an open handler');
            }
            const self = this;
            this.openHandler.load(id, (data) => {
                self.addDataSet(data, id, suffix, show);
                self.resize();
                callback && callback(data);
            });
        }

        /**
         * Returns the data from a dataset
         *
         * @this {DebugBar}
         * @param {string} id
         * @return {object}
         */
        getDataSet(id) {
            return this.datasets[id];
        }

        /**
         * Switch the currently displayed dataset
         *
         * @this {DebugBar}
         * @param {string} id
         */
        showDataSet(id) {
            this.activeDatasetId = id;
            if (this.isClosed()) {
                this.pendingDataSetId = id;
            } else {
                this.dataChangeHandler(this.datasets[id]);
                this.pendingDataSetId = null;
            }

            // Update dataset switcher widget to reflect current dataset
            if (this.datasetSwitcherWidget) {
                this.datasetSwitcherWidget.set('activeId', id);
            } else {
                // Update old dropdown
                this.datasetsSelect.value = id;
            }
        }

        /**
         * Called when the current dataset is modified.
         *
         * @this {DebugBar}
         * @param {object} data
         */
        dataChangeHandler(data) {
            for (const [key, def] of Object.entries(this.dataMap)) {
                const d = getDictValue(data, def[0], def[1]);
                if (key.includes(':')) {
                    const parts = key.split(':');
                    this.getControl(parts[0]).set(parts[1], d);
                } else {
                    this.getControl(key).set('data', d);
                }
            }

            if (!this.isMinimized()) {
                this.showTab();
            }

            this.resize();
        }

        /**
         * Sets the handler to open past dataset
         *
         * @this {DebugBar}
         * @param {object} handler
         */
        setOpenHandler(handler) {
            this.openHandler = handler;
            this.openHandler.el.setAttribute('data-theme', this.el.getAttribute('data-theme'));
            this.openbtn.hidden = handler == null;
        }

        /**
         * Returns the handler to open past dataset
         *
         * @this {DebugBar}
         * @return {object}
         */
        getOpenHandler() {
            return this.openHandler;
        }

        enableAjaxHandlerTab() {
            // Hide the old dropdown
            if (this.datasetsSelectSpan) {
                this.datasetsSelectSpan.hidden = true;
            }

            // Create dataset switcher widget in header (after open button)
            this.datasetSwitcherWidget = new PhpDebugBar.Widgets.DatasetWidget({
                debugbar: this
            });
            this.openbtn.after(this.datasetSwitcherWidget.el);
        }
    }

    PhpDebugBar.DebugBar = DebugBar;
    DebugBar.Tab = Tab;
    DebugBar.Indicator = Indicator;

    // ------------------------------------------------------------------

    /**
     * AjaxHandler
     *
     * Extract data from headers of an XMLHttpRequest and adds a new dataset
     *
     * @param {Bool} autoShow Whether to immediately show new datasets, optional (default: true)
     */
    class AjaxHandler {
        constructor(debugbar, headerName, autoShow) {
            this.debugbar = debugbar;
            this.headerName = headerName || 'phpdebugbar';
            this.autoShow = autoShow === undefined ? true : autoShow;
            this.defaultAutoShow = this.autoShow;
            if (localStorage.getItem('phpdebugbar-ajaxhandler-autoshow') !== null) {
                this.autoShow = localStorage.getItem('phpdebugbar-ajaxhandler-autoshow') === '1';
            }
            if (debugbar.controls.__settings) {
                debugbar.controls.__settings.get('widget').set('autoshow', this.autoShow);
            }
        }

        /**
         * Handles a Fetch API Response or an XMLHttpRequest
         *
         * @param {Response|XMLHttpRequest} response
         * @return {boolean}
         */
        handle(response) {
            const stack = this.getHeader(response, `${this.headerName}-stack`);
            if (stack) {
                const stackIds = JSON.parse(stack);
                stackIds.forEach((id) => {
                    this.debugbar.loadDataSet(id, ' (stacked)', null, false);
                });
            }

            if (this.loadFromId(response)) {
                return true;
            }

            if (this.loadFromData(response)) {
                return true;
            }

            return false;
        }

        /**
         * Retrieves a response header from either a Fetch Response or XMLHttpRequest
         *
         * @param {Response|XMLHttpRequest} response - The response object from either fetch() or XHR
         * @param {string} header - The name of the header to retrieve
         * @returns {string|null} The header value, or null if not found
         */
        getHeader(response, header) {
            if (response instanceof Response) {
                return response.headers.get(header);
            } else if (response instanceof XMLHttpRequest) {
                return response.getResponseHeader(header);
            }
            return null;
        }

        setAutoShow(autoshow) {
            this.autoShow = autoshow;
            localStorage.setItem('phpdebugbar-ajaxhandler-autoshow', autoshow ? '1' : '0');
        }

        /**
         * Checks if the HEADER-id exists and loads the dataset using the open handler
         *
         * @param {Response|XMLHttpRequest} response
         * @return {boolean}
         */
        loadFromId(response) {
            const id = this.extractIdFromHeaders(response);
            if (id && this.debugbar.openHandler) {
                this.debugbar.loadDataSet(id, '(ajax)', undefined, this.autoShow);
                return true;
            }
            return false;
        }

        /**
         * Extracts the id from the HEADER-id
         *
         * @param {Response|XMLHttpRequest} response
         * @return {string}
         */
        extractIdFromHeaders(response) {
            return this.getHeader(response, `${this.headerName}-id`);
        }

        /**
         * Checks if the HEADER exists and loads the dataset
         *
         * @param {Response|XMLHttpRequest} response
         * @return {boolean}
         */
        loadFromData(response) {
            const raw = this.extractDataFromHeaders(response);
            if (!raw) {
                return false;
            }

            const data = this.parseHeaders(raw);
            if (data.error) {
                throw new Error(`Error loading debugbar data: ${data.error}`);
            } else if (data.data) {
                this.debugbar.addDataSet(data.data, data.id, '(ajax)', this.autoShow);
            }
            return true;
        }

        /**
         * Extract the data as a string from headers of an XMLHttpRequest
         *
         * @param {Response|XMLHttpRequest} response
         * @return {string}
         */
        extractDataFromHeaders(response) {
            let data = this.getHeader(response, this.headerName);
            if (!data) {
                return;
            }
            for (let i = 1; ; i++) {
                const header = this.getHeader(response, `${this.headerName}-${i}`);
                if (!header) {
                    break;
                }
                data += header;
            }
            return decodeURIComponent(data);
        }

        /**
         * Parses the string data into an object
         *
         * @param {string} data
         * @return {object}
         */
        parseHeaders(data) {
            return JSON.parse(data);
        }

        /**
         * Attaches an event listener to fetch
         */
        bindToFetch() {
            const self = this;

            const proxied = window.fetch.__debugbar_original || window.fetch;
            const original = proxied.bind(window);

            function wrappedFetch(...args) {
                const p = original(...args);
                p?.then?.(r => self.handle(r)).catch(() => {});
                return p;
            }

            wrappedFetch.__debugbar_wrapped = true;
            wrappedFetch.__debugbar_original = proxied;

            window.fetch = wrappedFetch;
        }

        /**
         * Attaches an event listener to XMLHttpRequest
         */
        bindToXHR() {
            const self = this;
            const proto = XMLHttpRequest.prototype;

            const proxied = (proto.open || {}).__debugbar_original || proto.open;
            if (typeof proxied !== 'function') {
                return;
            }

            function wrappedOpen(method, url, async = true, user = null, pass = null) {
                if (!this.__debugbar_listener_attached) {
                    this.__debugbar_listener_attached = true;

                    this.addEventListener('readystatechange', () => {
                        if (this.readyState === 4) {
                            self.handle(this);
                        }
                    });
                }

                return proxied.call(this, method, url, async, user, pass);
            }

            wrappedOpen.__debugbar_wrapped = true;
            wrappedOpen.__debugbar_original = proxied;

            proto.open = wrappedOpen;
        }
    }

    PhpDebugBar.AjaxHandler = AjaxHandler;
})();

/* global phpdebugbar_hljs */
(function () {
    /**
     * @namespace
     */
    PhpDebugBar.Widgets = {};

    const csscls = PhpDebugBar.utils.makecsscls('phpdebugbar-widgets-');

    /**
     * Replaces spaces with &nbsp; and line breaks with <br>
     *
     * @param {string} text
     * @return {string}
     */
    const htmlize = PhpDebugBar.Widgets.htmlize = function (text) {
        return text.replace(/\n/g, '<br>').replace(/\s/g, '&nbsp;');
    };

    /**
     * Renders any value as a DOM element. Handles dump objects (with "_sd"
     * marker), and plain HTML/scalar strings.
     *
     * @param {string|object} value
     * @return {HTMLElement|string}
     */
    let dumpRenderer;
    const renderValue = PhpDebugBar.Widgets.renderValue = function (value, prettify) {
        // Dump object (from JsonDataFormatter)
        if (value && typeof value === 'object' && '_sd' in value) {
            if (!dumpRenderer) {
                dumpRenderer = new PhpDebugBar.Widgets.VarDumpRenderer();
            }
            return dumpRenderer.render(value);
        }

        if (typeof value !== 'string') {
            if (prettify) {
                return htmlize(JSON.stringify(value, undefined, 2));
            }
            return JSON.stringify(value);
        }

        return value;
    };

    PhpDebugBar.Widgets.renderValueInto = function (el, value, prettify) {
        const rendered = renderValue(value, prettify);
        if (rendered instanceof Node) {
            el.append(rendered);
        } else {
            el.insertAdjacentHTML('beforeend', rendered);
        }
    };

    /**
     * Creates html editor link span
     *
     * @param  {Object} value
     * @return {HTMLElement}
     */
    const editorLink = PhpDebugBar.Widgets.editorLink = function (value) {
        const linkWrapper = document.createElement('span'), line = value.line ? `#${value.line}` : '';
        linkWrapper.classList.add(csscls('filename'));
        linkWrapper.textContent = value.filename + line;
        if (value.path) {
            linkWrapper.setAttribute('title', value.path + line);
        }

        if (value.url) {
            const link = document.createElement('a');
            link.classList.add(csscls('editor-link'));
            link.setAttribute(link.ajax ? 'title' : 'href', value.url);
            link.addEventListener('click', (event) => {
                event.stopPropagation();
                if (value.ajax) {
                    fetch(stmt.xdebug_link.url);
                    event.preventDefault();
                }
            });
            linkWrapper.append(link);
        }

        return linkWrapper;
    };

    /**
     * Highlights a block of code
     *
     * @param  {string} code
     * @param  {string|null} lang
     * @return {string}
     */
    const highlight = PhpDebugBar.Widgets.highlight = function (code, lang) {
        if (typeof phpdebugbar_hljs === 'undefined') {
            return htmlize(code);
        }

        const hljs = phpdebugbar_hljs;
        if (lang && hljs.getLanguage(lang)) {
            return hljs.highlight(code, { language: lang }).value;
        }

        return hljs.highlightAuto(code).value;
    };

    /**
     * Creates a <pre> element with a block of code
     *
     * @param  {string} code
     * @param  {string} lang
     * @param  {number} [firstLineNumber] If provided, shows line numbers beginning with the given value.
     * @param  {number} [highlightedLine] If provided, the given line number will be highlighted.
     * @return {string}
     */
    const createCodeBlock = PhpDebugBar.Widgets.createCodeBlock = function (code, lang, firstLineNumber, highlightedLine) {
        const pre = document.createElement('pre');
        pre.classList.add(csscls('code-block'));

        // Add a newline to prevent <code> element from vertically collapsing too far if the last
        // code line was empty: that creates problems with the horizontal scrollbar being
        // incorrectly positioned - most noticeable when line numbers are shown.
        const codeElement = document.createElement('code');
        codeElement.innerHTML = highlight(`${code}\n`, lang);
        pre.append(codeElement);

        // Show line numbers in a list
        if (!Number.isNaN(Number.parseFloat(firstLineNumber))) {
            const lineCount = code.split('\n').length;
            const lineNumbers = document.createElement('ul');
            pre.prepend(lineNumbers);
            const children = Array.from(pre.children);
            for (const child of children) {
                child.classList.add(csscls('numbered-code'));
            }
            for (let i = firstLineNumber; i < firstLineNumber + lineCount; i++) {
                const li = document.createElement('li');
                li.textContent = i;
                lineNumbers.append(li);

                // Add a span with a special class if we are supposed to highlight a line.
                if (highlightedLine === i) {
                    li.classList.add(csscls('highlighted-line'));
                    const span = document.createElement('span');
                    span.innerHTML = '&nbsp;';
                    li.append(span);
                }
            }
        }

        return pre;
    };

    const { getDictValue } = PhpDebugBar.utils;

    // ------------------------------------------------------------------
    // Generic widgets
    // ------------------------------------------------------------------

    /**
     * Displays array element in a <ul> list
     *
     * Options:
     *  - data
     *  - itemRenderer: a function used to render list items (optional)
     */
    class ListWidget extends PhpDebugBar.Widget {
        get tagName() {
            return 'ul';
        }

        get className() {
            return csscls('list');
        }

        initialize(options) {
            if (!options.itemRenderer) {
                options.itemRenderer = this.itemRenderer;
            }
            this.set(options);
        }

        render() {
            this.bindAttr(['itemRenderer', 'data'], function () {
                this.el.innerHTML = '';
                if (!this.has('data')) {
                    return;
                }

                const data = this.get('data');
                for (let i = 0; i < data.length; i++) {
                    const li = document.createElement('li');
                    li.classList.add(csscls('list-item'));
                    this.el.append(li);
                    this.get('itemRenderer')(li, data[i]);
                }
            });
        }

        /**
         * Renders the content of a <li> element
         *
         * @param {HTMLElement} li The <li> element
         * @param {object} value An item from the data array
         */
        itemRenderer(li, value) {
            li.innerHTML = renderValue(value);
        }
    }
    PhpDebugBar.Widgets.ListWidget = ListWidget;

    // ------------------------------------------------------------------

    /**
     * Displays object property/value paris in a <dl> list
     *
     * Options:
     *  - data
     *  - itemRenderer: a function used to render list items (optional)
     */
    class KVListWidget extends ListWidget {
        get tagName() {
            return 'dl';
        }

        get className() {
            return csscls('kvlist');
        }

        render() {
            this.bindAttr(['itemRenderer', 'data'], function () {
                this.el.innerHTML = '';
                if (!this.has('data')) {
                    return;
                }

                for (const [key, value] of Object.entries(this.get('data'))) {
                    const dt = document.createElement('dt');
                    dt.classList.add(csscls('key'));
                    this.el.append(dt);

                    const dd = document.createElement('dd');
                    dd.classList.add(csscls('value'));
                    this.el.append(dd);

                    this.get('itemRenderer')(dt, dd, key, value);
                }
            });
        }

        /**
         * Renders the content of the <dt> and <dd> elements
         *
         * @param {HTMLElement} dt The <dt> element
         * @param {HTMLElement} dd The <dd> element
         * @param {string} key Property name
         * @param {object} value Property value
         */
        itemRenderer(dt, dd, key, value) {
            dt.textContent = key;
            dd.innerHTML = htmlize(value);
        }
    }
    PhpDebugBar.Widgets.KVListWidget = KVListWidget;

    // ------------------------------------------------------------------

    /**
     * An extension of KVListWidget where the data represents a list
     * of variables
     *
     * Options:
     *  - data
     */
    class VariableListWidget extends KVListWidget {
        get className() {
            return csscls('kvlist varlist');
        }

        itemRenderer(dt, dd, key, value) {
            const span = document.createElement('span');
            span.setAttribute('title', key);
            span.textContent = key;
            dt.append(span);

            let v = value && value.value || value;
            if (v && v.length > 100) {
                v = `${v.substr(0, 100)}...`;
            }
            let prettyVal = null;
            dd.textContent = v;
            dd.addEventListener('click', () => {
                if (window.getSelection().type === 'Range') {
                    return '';
                }
                if (dd.classList.contains(csscls('pretty'))) {
                    dd.textContent = v;
                    dd.classList.remove(csscls('pretty'));
                } else {
                    prettyVal = prettyVal || createCodeBlock(value);
                    dd.classList.add(csscls('pretty'));
                    dd.innerHTML = '';
                    dd.append(prettyVal);
                }
            });
        }
    }
    PhpDebugBar.Widgets.VariableListWidget = VariableListWidget;

    // ------------------------------------------------------------------

    /**
     * An extension of KVListWidget where the data represents a list
     * of variables whose contents are HTML; this is useful for showing
     * variable output from VarDumper's HtmlDumper.
     *
     * Options:
     *  - data
     */
    class HtmlVariableListWidget extends KVListWidget {
        get className() {
            return csscls('kvlist htmlvarlist');
        }

        itemRenderer(dt, dd, key, value) {
            const tempElement = document.createElement('i');
            tempElement.innerHTML = key ?? '';
            const span = document.createElement('span');
            span.setAttribute('title', tempElement.textContent);
            span.innerHTML = key ?? '';
            dt.append(span);

            dd.innerHTML = value && value.value || value;

            if (value?.xdebug_link) {
                dd.append(editorLink(value.xdebug_link));
            }
        }
    }
    PhpDebugBar.Widgets.HtmlVariableListWidget = HtmlVariableListWidget;

    // ------------------------------------------------------------------

    /**
     * Displays array element in a <table> list, columns keys map
     * useful for showing a multiple values table
     *
     * Options:
     *  - data
     *  - key_map: list of keys to be displayed with an optional label
     *             example: {key1: label1, key2: label2} or [key1, key2]
     */
    class TableVariableListWidget extends PhpDebugBar.Widget {
        get tagName() {
            return 'div';
        }

        get className() {
            return csscls('tablevarlist');
        }

        render() {
            this.bindAttr('data', function (data) {
                this.el.innerHTML = '';

                if (!this.has('data')) {
                    return;
                }

                this.table = document.createElement('table');
                this.table.classList.add(csscls('tablevar'));
                this.el.append(this.table);

                const header = document.createElement('tr');
                header.classList.add(csscls('header'));
                const headerFirstCell = document.createElement('td');
                header.append(headerFirstCell);
                this.table.append(header);

                let key_map = data.key_map || { value: 'Value' };

                if (Array.isArray(key_map)) {
                    key_map = Object.fromEntries(key_map.map(k => [k, null]));
                }

                for (const [key, label] of Object.entries(key_map)) {
                    const colTitle = document.createElement('td');
                    colTitle.textContent = label ?? key;
                    header.append(colTitle);

                    if (data.badges && data.badges[key]) {
                        const badge = document.createElement('span');
                        badge.textContent = data.badges[key];
                        badge.classList.add(csscls('badge'));
                        colTitle.append(badge);
                    }
                }

                const self = this;
                if (!data.data) {
                    return;
                }
                let hasXdebuglinks = false;
                for (const [key, values] of Object.entries(data.data)) {
                    const tr = document.createElement('tr');
                    tr.classList.add(csscls('item'));
                    self.table.append(tr);

                    const keyCell = document.createElement('td');
                    keyCell.classList.add(csscls('key'));
                    keyCell.textContent = key;
                    tr.append(keyCell);

                    if (typeof values !== 'object' || values === null) {
                        const valueCell = document.createElement('td');
                        valueCell.classList.add(csscls('value'));
                        valueCell.textContent = values ?? '';
                        tr.append(valueCell);
                        continue;
                    }

                    for (const key of Object.keys(key_map)) {
                        const valueCell = document.createElement('td');
                        valueCell.classList.add(csscls('value'));
                        valueCell.textContent = values[key] ?? '';
                        tr.append(valueCell);
                    }

                    if (values.xdebug_link) {
                        const editorCell = document.createElement('td');
                        editorCell.classList.add(csscls('editor'));
                        editorCell.append(editorLink(values.xdebug_link));
                        tr.append(editorCell);

                        if (!hasXdebuglinks) {
                            hasXdebuglinks = true;
                            header.append(document.createElement('td'));
                        }
                    }
                }

                if (!data.summary) {
                    return;
                }

                const summaryTr = document.createElement('tr');
                summaryTr.classList.add(csscls('summary'));
                self.table.append(summaryTr);

                const summaryKeyCell = document.createElement('td');
                summaryKeyCell.classList.add(csscls('key'));
                summaryTr.append(summaryKeyCell);

                if (typeof data.summary !== 'object' || data.summary === null) {
                    const summaryValueCell = document.createElement('td');
                    summaryValueCell.classList.add(csscls('value'));
                    summaryValueCell.textContent = data.summary ?? '';
                    summaryTr.append(summaryValueCell);
                } else {
                    for (const key of Object.keys(key_map)) {
                        const summaryValueCell = document.createElement('td');
                        summaryValueCell.classList.add(csscls('value'));
                        summaryValueCell.textContent = data.summary[key] ?? '';
                        summaryTr.append(summaryValueCell);
                    }
                }

                if (hasXdebuglinks) {
                    summaryTr.append(document.createElement('td'));
                }
            });
        }
    }
    PhpDebugBar.Widgets.TableVariableListWidget = TableVariableListWidget;

    // ------------------------------------------------------------------

    /**
     * Iframe widget
     *
     * Options:
     *  - data
     */
    class IFrameWidget extends PhpDebugBar.Widget {
        get tagName() {
            return 'iframe';
        }

        get className() {
            return csscls('iframe');
        }

        render() {
            this.el.setAttribute('seamless', 'seamless');
            this.el.setAttribute('border', '0');
            this.el.setAttribute('width', '100%');
            this.el.setAttribute('height', '100%');

            this.bindAttr('data', function (url) {
                this.el.setAttribute('src', url);
            });
        }
    }
    PhpDebugBar.Widgets.IFrameWidget = IFrameWidget;

    // ------------------------------------------------------------------
    // Collector specific widgets
    // ------------------------------------------------------------------

    /**
     * Widget for the MessagesCollector
     *
     * Uses ListWidget under the hood
     *
     * Options:
     *  - data
     */
    class MessagesWidget extends PhpDebugBar.Widget {
        get className() {
            return csscls('messages');
        }

        render() {
            const self = this;

            this.list = new ListWidget({ itemRenderer(li, value) {
                let val;
                if (value.message_json) {
                    val = document.createElement('span');
                    val.classList.add(csscls('value'));
                    PhpDebugBar.Widgets.renderValueInto(val, value.message_json);
                    li.append(val);
                } else if (value.message_html) {
                    val = document.createElement('span');
                    val.classList.add(csscls('value'));
                    val.innerHTML = value.message_html;
                    li.append(val);
                } else {
                    const m = value.message;

                    val = document.createElement('span');
                    val.classList.add(csscls('value'));
                    val.textContent = m;
                    val.classList.add(csscls('truncated'));
                    li.append(val);

                    if (!value.is_string || val.scrollWidth > val.clientWidth) {
                        let prettyVal = value.message;
                        if (!value.is_string) {
                            prettyVal = null;
                        }
                        li.style.cursor = 'pointer';
                        li.addEventListener('click', () => {
                            if (window.getSelection().type === 'Range') {
                                return '';
                            }
                            if (val.classList.contains(csscls('pretty'))) {
                                val.textContent = m;
                                val.classList.remove(csscls('pretty'));
                                val.classList.add(csscls('truncated'));
                            } else {
                                prettyVal = prettyVal || createCodeBlock(value.message);
                                val.classList.add(csscls('pretty'));
                                val.classList.remove(csscls('truncated'));
                                val.innerHTML = '';
                                val.append(prettyVal);
                            }
                        });
                    }
                }
                if (value.collector) {
                    const collector = document.createElement('span');
                    collector.classList.add(csscls('collector'));
                    collector.textContent = value.collector;
                    li.prepend(collector);
                }
                if (value.label) {
                    val.classList.add(csscls(value.label));
                    const label = document.createElement('span');
                    label.classList.add(csscls('label'));
                    label.textContent = value.label;
                    li.prepend(label);
                }
                if (value.context && Object.keys(value.context).length > 0) {
                    const contextCount = document.createElement('span');
                    contextCount.setAttribute('title', 'Context');
                    contextCount.classList.add(csscls('context-count'));
                    contextCount.textContent = Object.keys(value.context).length;
                    li.prepend(contextCount);

                    const contextTable = document.createElement('table');
                    contextTable.classList.add(csscls('params'));
                    contextTable.hidden = true;
                    contextTable.innerHTML = '<tr><th colspan="2">Context</th></tr>';

                    const contextJsonData = value.context_json || {};
                    for (const key in value.context) {
                        if (typeof value.context[key] !== 'function') {
                            const tr = document.createElement('tr');
                            const td1 = document.createElement('td');
                            td1.classList.add(csscls('name'));
                            td1.textContent = key;
                            tr.append(td1);

                            const td2 = document.createElement('td');
                            td2.classList.add(csscls('value'));
                            if (contextJsonData[key]) {
                                PhpDebugBar.Widgets.renderValueInto(td2, contextJsonData[key]);
                            } else {
                                td2.innerHTML = value.context[key];
                            }
                            tr.append(td2);

                            contextTable.append(tr);
                        }
                    }
                    li.append(contextTable);

                    li.style.cursor = 'pointer';
                    li.addEventListener('click', (event) => {
                        if (window.getSelection().type === 'Range' || event.target.closest('.sf-dump')) {
                            return;
                        }
                        contextTable.hidden = !contextTable.hidden;
                    });
                }
                if (value.xdebug_link) {
                    li.prepend(editorLink(value.xdebug_link));
                }
            } });

            this.el.append(this.list.el);

            this.toolbar = document.createElement('div');
            this.toolbar.classList.add(csscls('toolbar'));
            this.toolbar.innerHTML = '<i class="phpdebugbar-icon phpdebugbar-icon-search"></i>';
            this.el.append(this.toolbar);

            const searchInput = document.createElement('input');
            searchInput.type = 'text';
            searchInput.name = 'search';
            searchInput.setAttribute('aria-label', 'Search');
            searchInput.placeholder = 'Search';
            searchInput.addEventListener('change', function () {
                self.set('search', this.value);
            });
            this.toolbar.append(searchInput);

            this.bindAttr('data', function (data) {
                this.set({ excludelabel: [], excludecollector: [], search: '' });

                const filters = this.toolbar.querySelectorAll(`.${csscls('filter')}`);
                for (const filter of filters) {
                    filter.remove();
                }

                const labels = []; const collectors = []; const self = this;
                const createFilterItem = function (type, value) {
                    const link = document.createElement('a');
                    link.classList.add(csscls('filter'));
                    link.classList.add(csscls(type));
                    link.textContent = value;
                    link.setAttribute('rel', value);
                    link.addEventListener('click', function () {
                        self.onFilterClick(this, type);
                    });
                    self.toolbar.append(link);
                };

                data.forEach((item) => {
                    if (!labels.includes(item.label || 'none')) {
                        labels.push(item.label || 'none');
                    }

                    if (!collectors.includes(item.collector || 'none')) {
                        collectors.push(item.collector || 'none');
                    }
                });

                if (labels.length > 1) {
                    labels.forEach(label => createFilterItem('label', label));
                }

                if (collectors.length === 1) {
                    return;
                }

                const spacer = document.createElement('a');
                spacer.classList.add(csscls('filter'));
                spacer.style.visibility = 'hidden';
                self.toolbar.append(spacer);
                collectors.forEach(collector => createFilterItem('collector', collector));
            });

            this.bindAttr(['excludelabel', 'excludecollector', 'search'], function () {
                const excludelabel = this.get('excludelabel') || [];
                const excludecollector = this.get('excludecollector') || [];
                const search = this.get('search');
                let caseless = false;
                const fdata = [];

                if (search && search === search.toLowerCase()) {
                    caseless = true;
                }

                this.get('data').forEach((item) => {
                    const message = caseless ? item.message.toLowerCase() : item.message;

                    if (
                        !excludelabel.includes(item.label || undefined)
                        && !excludecollector.includes(item.collector || undefined)
                        && (!search || message.includes(search))
                    ) {
                        fdata.push(item);
                    }
                });

                this.list.set('data', fdata);
            });
        }

        onFilterClick(el, type) {
            el.classList.toggle(csscls('excluded'));

            const excluded = [];
            const selector = `.${csscls('filter')}.${csscls('excluded')}.${csscls(type)}`;
            const excludedFilters = this.toolbar.querySelectorAll(selector);
            for (const filter of excludedFilters) {
                excluded.push(filter.rel === 'none' || !filter.rel ? undefined : filter.rel);
            }

            this.set(`exclude${type}`, excluded);
        }
    }
    PhpDebugBar.Widgets.MessagesWidget = MessagesWidget;

    // ------------------------------------------------------------------

    /**
     * Widget for the TimeDataCollector
     *
     * Options:
     *  - data
     */
    class TimelineWidget extends PhpDebugBar.Widget {
        get tagName() {
            return 'ul';
        }

        get className() {
            return csscls('timeline');
        }

        render() {
            this.bindAttr('data', function (data) {
                // ported from php DataFormatter
                const formatDuration = function (seconds) {
                    if (seconds < 0.001) {
                        return `${(seconds * 1000000).toFixed()}μs`;
                    } else if (seconds < 0.1) {
                        return `${(seconds * 1000).toFixed(2)}ms`;
                    } else if (seconds < 1) {
                        return `${(seconds * 1000).toFixed()}ms`;
                    }
                    return `${(seconds).toFixed(2)}s`;
                };

                // ported from php DataFormatter
                const formatBytes = function formatBytes(size) {
                    if (size === 0 || size === null) {
                        return '0B';
                    }

                    const sign = size < 0 ? '-' : '';
                    const absSize2 = Math.abs(size);
                    const base = Math.log(absSize2) / Math.log(1024);
                    const suffixes = ['B', 'KB', 'MB', 'GB', 'TB'];
                    return sign + (Math.round(1024 ** (base - Math.floor(base)) * 100) / 100) + suffixes[Math.floor(base)];
                };

                this.el.innerHTML = '';
                if (data.measures) {
                    let aggregate = {};

                    for (let i = 0; i < data.measures.length; i++) {
                        const measure = data.measures[i];
                        const group = measure.group || measure.label;
                        const values = measure.values || [{
                            relative_start: measure.relative_start,
                            duration: measure.duration,
                        }];

                        if (!aggregate[group]) {
                            aggregate[group] = { count: 0, duration: 0, memory: 0 };
                        }

                        aggregate[group].count += values.length;
                        aggregate[group].duration += measure.duration;
                        aggregate[group].memory += (measure.memory || 0);

                        const m = document.createElement('div');
                        m.classList.add(csscls('measure'));

                        const li = document.createElement('li');
                        for (let j = 0; j < values.length; j++) {
                            const left = (values[j].relative_start * 100 / data.duration).toFixed(2);
                            const width = Math.min((values[j].duration * 100 / data.duration).toFixed(2), 100 - left);

                            const valueSpan = document.createElement('span');
                            valueSpan.classList.add(csscls('value'));
                            valueSpan.style.left = `${left}%`;
                            valueSpan.style.width = `${width}%`;
                            m.append(valueSpan);
                        }

                        const labelSpan = document.createElement('span');
                        labelSpan.classList.add(csscls('label'));
                        labelSpan.textContent = (values.length > 1 ? values.length + 'x ' : '') + measure.label.replace(/\s+/g, ' ')
                            + (measure.duration ? ` (${measure.duration_str}${measure.memory ? `/${measure.memory_str}` : ''})` : '');
                        m.append(labelSpan);

                        if (measure.collector) {
                            const collectorSpan = document.createElement('span');
                            collectorSpan.classList.add(csscls('collector'));
                            collectorSpan.textContent = measure.collector;
                            m.append(collectorSpan);
                        }

                        li.append(m);
                        this.el.append(li);

                        if (measure.params && Object.keys(measure.params).length > 0) {
                            const table = document.createElement('table');
                            table.classList.add(csscls('params'));
                            table.hidden = true;
                            table.innerHTML = '<tr><th colspan="2">Params</th></tr>';

                            for (const key in measure.params) {
                                if (typeof measure.params[key] !== 'function') {
                                    const tr = document.createElement('tr');
                                    const nameTd = document.createElement('td');
                                    nameTd.className = csscls('name');
                                    nameTd.textContent = key;
                                    tr.append(nameTd);

                                    const valueTd = document.createElement('td');
                                    valueTd.className = csscls('value');
                                    PhpDebugBar.Widgets.renderValueInto(valueTd, measure.params[key]);
                                    tr.append(valueTd);
                                    table.append(tr);
                                }
                            }
                            li.append(table);

                            li.style.cursor = 'pointer';
                            li.addEventListener('click', function (event) {
                                if (window.getSelection().type === 'Range' || event.target.closest('.sf-dump')) {
                                    return '';
                                }
                                const table = this.querySelector('table');
                                table.hidden = !table.hidden;
                            });

                        }
                    }

                    // convert to array and sort by duration
                    aggregate = Object.entries(aggregate).map(([label, data]) => ({
                        label,
                        data
                    })).sort((a, b) => {
                        return b.data.duration - a.data.duration;
                    });

                    // build table and add
                    const aggregateTable = document.createElement('table');
                    aggregateTable.classList.add(csscls('params'));

                    for (const agg of aggregate) {
                        const width = Math.min((agg.data.duration * 100 / data.duration).toFixed(2), 100);

                        const tempElement = document.createElement('i');
                        tempElement.textContent = agg.label.replace(/\s+/g, ' ');
                        const escapedLabel = tempElement.innerHTML;

                        const tr = document.createElement('tr');
                        tr.innerHTML = `<td class="${csscls('name')}">${
                            agg.data.count} x ${escapedLabel} (${width}%)</td><td class="${csscls('value')}">`
                            + `<div class="${csscls('measure')}">`
                            + `<span class="${csscls('value')}"></span>`
                            + `<span class="${csscls('label')}">${formatDuration(agg.data.duration)}${agg.data.memory ? `/${formatBytes(agg.data.memory)}` : ''}</span>`
                            + '</div></td>';
                        aggregateTable.append(tr);

                        const lastValueSpan = tr.querySelector(`span.${csscls('value')}`);
                        lastValueSpan.style.width = `${width}%`;
                    }

                    const lastLi = document.createElement('li');
                    lastLi.append(aggregateTable);
                    this.el.append(lastLi);
                }
            });
        }
    }
    PhpDebugBar.Widgets.TimelineWidget = TimelineWidget;

    // ------------------------------------------------------------------

    /**
     * Widget for the displaying exceptions
     *
     * Options:
     *  - data
     */
    class ExceptionsWidget extends PhpDebugBar.Widget {
        get className() {
            return csscls('exceptions');
        }

        render() {
            this.list = new ListWidget({ itemRenderer(li, e) {
                const messageSpan = document.createElement('span');
                messageSpan.classList.add(csscls('message'));
                messageSpan.textContent = e.message;

                if (e.count > 1) {
                    const badge = document.createElement('span');
                    badge.classList.add(csscls('badge'));
                    badge.textContent = `${e.count}x`;
                    messageSpan.prepend(badge);
                }
                li.append(messageSpan);

                if (e.file) {
                    li.append(editorLink(e.xdebug_link || {filename: e.file, line: e.line}));
                }

                if (e.type) {
                    const typeSpan = document.createElement('span');
                    typeSpan.classList.add(csscls('type'));
                    typeSpan.textContent = e.type;
                    li.append(typeSpan);
                }

                if (e.surrounding_lines) {
                    const startLine = (e.line - 3) <= 0 ? 1 : e.line - 3;
                    const pre = createCodeBlock(e.surrounding_lines.join(''), 'php', startLine, e.line);
                    pre.classList.add(csscls('file'));
                    pre.hidden = true;
                    li.append(pre);

                    // This click event makes the var-dumper hard to use.
                    li.addEventListener('click', (event) => {
                        if (window.getSelection().type === 'Range' || event.target.closest('.sf-dump')) {
                            return;
                        }
                        pre.hidden = !pre.hidden;
                    });
                }

                if (e.stack_trace_html) {
                    const trace = document.createElement('span');
                    trace.classList.add(csscls('filename'));
                    trace.innerHTML = e.stack_trace_html;

                    const samps = trace.querySelectorAll('samp[data-depth="1"]');
                    for (const samp of samps) {
                        samp.classList.remove('sf-dump-expanded');
                        samp.classList.add('sf-dump-compact');

                        const note = samp.parentElement.querySelector(':scope > .sf-dump-note');
                        if (note) {
                            note.innerHTML = `${note.innerHTML.replace(/^array:/, '<span class="sf-dump-key">Stack Trace:</span> ')} files`;
                        }
                    }
                    li.append(trace);
                } else if (e.stack_trace) {
                    e.stack_trace.split('\n').forEach((trace) => {
                        const traceLine = document.createElement('div');
                        const filename = document.createElement('span');
                        filename.classList.add(csscls('filename'));
                        filename.textContent = trace;
                        traceLine.append(filename);
                        li.append(traceLine);
                    });
                }
            } });
            this.el.append(this.list.el);

            this.bindAttr('data', function (data) {
                this.list.set('data', data);
                if (data.length === 1) {
                    const firstChild = this.list.el.children[0];
                    if (firstChild) {
                        const file = firstChild.querySelector(`.${csscls('file')}`);
                        if (file) {
                            file.hidden = false;
                        }
                    }
                }
            });
        }
    }
    PhpDebugBar.Widgets.ExceptionsWidget = ExceptionsWidget;

    // ------------------------------------------------------------------

    /**
     * Dataset Switcher Widget
     *
     * Displays a compact badge showing the current request with a dropdown
     * to switch between different datasets
     */
    class DatasetWidget extends PhpDebugBar.Widget {
        get className() {
            return csscls('datasets-switcher-widget');
        }

        initialize(options) {
            this.set(options);

            const self = this;
            const debugbar = this.get('debugbar');

            // Create badge
            this.badge = document.createElement('div');
            this.badge.classList.add(csscls('datasets-badge'));
            this.badge.hidden = true;

            this.badgeCount = document.createElement('span');
            this.badgeCount.classList.add(csscls('datasets-badge-count'));
            this.badge.append(this.badgeCount);

            this.badgeUrl = document.createElement('span');
            this.badgeUrl.classList.add(csscls('datasets-badge-url'));
            this.badge.append(this.badgeUrl);

            // Create dropdown panel
            this.panel = document.createElement('div');
            this.panel.classList.add(csscls('datasets-panel'));
            this.panel.hidden = true;
            // Copy theme from debugbar to panel for CSS variable inheritance
            if (debugbar.el) {
                this.panel.setAttribute('data-theme', debugbar.el.getAttribute('data-theme'));
            }

            // Panel toolbar with filters
            const toolbar = document.createElement('div');
            toolbar.classList.add(csscls('datasets-panel-toolbar'));

            // Autoshow checkbox
            const autoshowLabel = document.createElement('label');
            autoshowLabel.classList.add(csscls('datasets-autoshow'));
            this.autoshowCheckbox = document.createElement('input');
            this.autoshowCheckbox.type = 'checkbox';
            // Get initial value from localStorage or ajaxHandler or default to true
            const storedAutoShow = localStorage.getItem('phpdebugbar-ajaxhandler-autoshow');
            this.autoshowCheckbox.checked = storedAutoShow !== null
                ? storedAutoShow === '1'
                : (debugbar.ajaxHandler ? debugbar.ajaxHandler.autoShow : true);
            this.autoshowCheckbox.addEventListener('change', function () {
                if (debugbar.ajaxHandler) {
                    debugbar.ajaxHandler.setAutoShow(this.checked);
                }
                // Update settings widget if it exists
                if (debugbar.controls.__settings) {
                    debugbar.controls.__settings.get('widget').set('autoshow', this.checked);
                }
                // Update dataset tab widget if it exists
                if (debugbar.controls.__datasets) {
                    debugbar.controls.__datasets.get('widget').set('autoshow', this.checked);
                }
            });
            autoshowLabel.append(this.autoshowCheckbox);
            autoshowLabel.append(document.createTextNode(' Autoshow'));
            toolbar.append(autoshowLabel);

            // Refresh button
            this.refreshBtn = document.createElement('a');
            this.refreshBtn.tabIndex = 0;
            this.refreshBtn.classList.add(csscls('datasets-refresh-btn'));
            this.refreshBtn.innerHTML = '<i class="phpdebugbar-icon phpdebugbar-icon-refresh"></i>';
            this.refreshBtn.title = 'Auto-scan for new datasets';
            this.isScanning = false;
            this.refreshBtn.addEventListener('click', (e) => {
                e.stopPropagation(); // Prevent panel from closing
                if (this.isScanning) {
                    // Stop scanning
                    this.isScanning = false;
                    this.refreshBtn.classList.remove(csscls('active'));
                    this.refreshBtn.title = 'Auto-scan for new datasets';
                } else {
                    // Start scanning
                    this.isScanning = true;
                    this.refreshBtn.classList.add(csscls('active'));
                    this.refreshBtn.title = 'Stop auto-scanning';
                    // Start first scan immediately
                    this.scanForNewDatasets();
                }
            });
            toolbar.append(this.refreshBtn);

            // Clear button
            const clearBtn = document.createElement('a');
            clearBtn.tabIndex = 0;
            clearBtn.classList.add(csscls('datasets-clear-btn'));
            clearBtn.textContent = 'Clear';
            clearBtn.addEventListener('click', () => {
                const currentId = debugbar.activeDatasetId;
                const currentDataset = debugbar.datasets[currentId];
                debugbar.datasets = {};
                if (currentDataset) {
                    debugbar.addDataSet(currentDataset, currentId, currentDataset.__meta.suffix, true);
                }
                this.panel.hidden = true;
            });
            toolbar.append(clearBtn);

            // Search input
            this.searchInput = document.createElement('input');
            this.searchInput.type = 'search';
            this.searchInput.placeholder = 'Search';
            this.searchInput.classList.add(csscls('datasets-search'));
            this.searchInput.addEventListener('input', () => {
                self.applySearchFilter();
            });
            toolbar.append(this.searchInput);

            this.panel.append(toolbar);

            this.list = document.createElement('div');
            this.list.classList.add(csscls('datasets-list'));
            this.panel.append(this.list);

            this.el.append(this.badge);
            document.body.append(this.panel);

            // Position panel relative to badge
            const positionPanel = () => {
                const badgeRect = this.badge.getBoundingClientRect();

                // Calculate available space above and below the badge
                const spaceAbove = badgeRect.top;
                const spaceBelow = window.innerHeight - badgeRect.bottom;
                const showBelow = spaceBelow > spaceAbove;

                this.panel.style.position = 'fixed';
                this.panel.style.right = `${window.innerWidth - badgeRect.right}px`;
                this.panel.style.left = 'auto';

                if (showBelow) {
                    // Show panel below badge
                    this.panel.style.top = `${badgeRect.bottom}px`;
                    this.panel.style.bottom = 'auto';
                    this.panel.style.maxHeight = `${spaceBelow}px`;
                } else {
                    // Show panel above badge (no gap)
                    this.panel.style.bottom = `${window.innerHeight - badgeRect.top}px`;
                    this.panel.style.top = 'auto';
                    this.panel.style.maxHeight = `${spaceAbove}px`;
                }

                this.refreshBtn.hidden = !debugbar.openHandler;
            };

            // Toggle panel on click
            this.badge.addEventListener('click', (e) => {
                if (e.target !== this.panel && !this.panel.contains(e.target)) {
                    if (this.panel.hidden) {
                        positionPanel();
                    }
                    this.panel.hidden = !this.panel.hidden;
                }
            });

            // Close panel when clicking outside
            document.addEventListener('click', (e) => {
                if (!this.badge.contains(e.target) && !this.panel.contains(e.target)) {
                    this.panel.hidden = true;
                }
            });
        }

        render() {
            // Bind to data changes
            this.bindAttr('data', function () {
                this.updateBadge();
            });

            this.bindAttr('activeId', function () {
                this.updateBadge();
            });

            // Bind to autoshow changes from settings
            this.bindAttr('autoshow', function () {
                if (this.autoshowCheckbox) {
                    this.autoshowCheckbox.checked = this.get('autoshow');
                }
            });
        }

        updateBadge() {
            const debugbar = this.get('debugbar');
            const datasets = this.get('data') || debugbar.datasets;
            const activeId = this.get('activeId') || debugbar.activeDatasetId;

            if (!datasets) {
                this.badge.hidden = true;
                return;
            }

            const datasetCount = Object.keys(datasets).length;

            if (datasetCount >= 1) {
                this.badge.hidden = false;

                // Update the current URL display
                const currentDataset = datasets[activeId];
                if (currentDataset && currentDataset.__meta) {
                    const uri = currentDataset.__meta.uri || '';
                    const method = currentDataset.__meta.method || 'GET';
                    this.badgeUrl.textContent = `${method} ${uri}`;
                }

                // Only show count badge if more than 1 request
                if (datasetCount > 1) {
                    this.badgeCount.textContent = datasetCount;
                    this.badgeCount.hidden = false;
                } else {
                    this.badgeCount.hidden = true;
                }

                // Clear and rebuild the panel list
                this.list.innerHTML = '';

                // Get all datasets and sort by utime (latest on top)
                const datasetIds = Object.keys(datasets).sort((a, b) => {
                    const utimeA = datasets[a].__meta?.utime || 0;
                    const utimeB = datasets[b].__meta?.utime || 0;
                    return utimeB - utimeA; // Descending order (latest first)
                });

                for (const datasetId of datasetIds) {
                    const dataset = datasets[datasetId];
                    const item = document.createElement('div');
                    item.classList.add(csscls('datasets-list-item'));

                    // Store data attributes for filtering
                    const uri = dataset.__meta.uri || '';
                    const method = dataset.__meta.method || 'GET';
                    item.setAttribute('data-url', uri);
                    item.setAttribute('data-method', method);

                    if (datasetId === activeId) {
                        item.classList.add(csscls('active'));
                    }

                    // Request number
                    const nb = document.createElement('span');
                    nb.classList.add(csscls('datasets-item-nb'));
                    nb.textContent = `#${dataset.__meta.nb}`;
                    item.append(nb);

                    // Time
                    const time = document.createElement('span');
                    time.classList.add(csscls('datasets-item-time'));
                    time.textContent = dataset.__meta.datetime ? dataset.__meta.datetime.split(' ')[1] : '';
                    item.append(time);

                    // Request info
                    const request = document.createElement('div');
                    request.classList.add(csscls('datasets-item-request'));

                    const methodSpan = document.createElement('span');
                    methodSpan.classList.add(csscls('datasets-item-method'));
                    methodSpan.textContent = method;
                    request.append(methodSpan);

                    const url = document.createElement('span');
                    url.classList.add(csscls('datasets-item-url'));
                    url.textContent = uri;
                    request.append(url);

                    if (dataset.__meta.suffix) {
                        const suffix = document.createElement('span');
                        suffix.classList.add(csscls('datasets-item-suffix'));
                        suffix.textContent = ` ${dataset.__meta.suffix}`;
                        request.append(suffix);
                    }

                    item.append(request);

                    // Data badges (icons with counts)
                    const badges = document.createElement('div');
                    badges.classList.add(csscls('datasets-item-badges'));

                    for (const [key, def] of Object.entries(debugbar.dataMap)) {
                        const d = getDictValue(dataset, def[0], def[1]);
                        if (key.includes(':')) {
                            const parts = key.split(':');
                            const controlKey = parts[0];
                            const subkey = parts[1];

                            if (subkey === 'badge' && d > 0) {
                                const control = debugbar.getControl(controlKey);
                                if (control) {
                                    const badge = document.createElement('span');
                                    badge.classList.add(csscls('datasets-item-badge'));
                                    badge.setAttribute('title', control.get('title'));
                                    badge.dataset.tab = controlKey;

                                    if (control.icon) {
                                        const iconClone = control.icon.cloneNode(true);
                                        iconClone.style.width = '12px';
                                        iconClone.style.height = '12px';
                                        badge.append(iconClone);
                                    }

                                    const count = document.createElement('span');
                                    count.textContent = d;
                                    badge.append(count);

                                    badges.append(badge);

                                    // Click badge to show that tab
                                    badge.addEventListener('click', (e) => {
                                        e.stopPropagation();
                                        debugbar.showDataSet(datasetId);
                                        debugbar.showTab(controlKey);
                                        this.panel.hidden = true;
                                    });
                                }
                            }
                        }
                    }
                    item.append(badges);

                    // Click handler
                    item.addEventListener('click', () => {
                        debugbar.showDataSet(datasetId);
                        this.panel.hidden = true;
                    });

                    this.list.append(item);
                }

                // Reapply search filter if there's a search value
                this.applySearchFilter();
            } else {
                this.badge.hidden = true;
            }
        }

        applySearchFilter() {
            const searchValue = this.searchInput.value.toLowerCase().trim();
            const items = this.list.querySelectorAll(`.${csscls('datasets-list-item')}`);

            for (const item of items) {
                if (searchValue === '') {
                    item.hidden = false;
                } else {
                    const url = item.getAttribute('data-url').toLowerCase();
                    const method = item.getAttribute('data-method').toLowerCase();
                    const searchText = `${method} ${url}`;

                    // Split search terms by spaces and check if ALL terms match
                    const searchTerms = searchValue.split(/\s+/).filter(term => term.length > 0);
                    const matches = searchTerms.every(term => searchText.includes(term));

                    item.hidden = !matches;
                }
            }
        }

        scanForNewDatasets() {
            const debugbar = this.get('debugbar');
            if (!this.isScanning || !debugbar.openHandler)
                return;

            const datasets = debugbar.datasets;

            const latestUtime = Object.values(datasets)
                .reduce((max, d) => Math.max(max, d.__meta?.utime || 0), 0);

            const scheduleNextScan = () => {
                if (this.isScanning) {
                    setTimeout(() => this.scanForNewDatasets(), 1000);
                }
            };

            debugbar.openHandler.find({ utime: latestUtime }, 0, (data, err) => {
                // Abort on explicit error argument
                if (err) {
                    console.error('scanForNewDatasets: find() failed', err);
                    this.isScanning = false;
                    this.refreshBtn.classList.remove(csscls('active'));
                    this.refreshBtn.title = 'Error scanning';
                    return;
                }

                try {
                    const newDatasets = data.filter(
                        meta => meta.utime > latestUtime && !datasets[meta.id]
                    );

                    // Reverse to load oldest first (since find() returns newest first)
                    newDatasets.reverse();

                    const loadNext = (index = 0) => {
                        if (index >= newDatasets.length) {
                            scheduleNextScan();
                            return;
                        }

                        const { id } = newDatasets[index];
                        const isLast = index === newDatasets.length - 1;
                        debugbar.loadDataSet(
                            id,
                            '(scan)',
                            () => loadNext(index + 1),
                            this.autoshowCheckbox.checked && isLast
                        );
                    };

                    newDatasets.length ? loadNext() : scheduleNextScan();
                } catch (error) {
                    console.error('scanForNewDatasets: unexpected error', error);
                    this.refreshBtn.classList.remove(csscls('active'));
                    this.refreshBtn.title = 'Error scanning';
                    this.isScanning = false;
                }
            });
        }
    }
    PhpDebugBar.Widgets.DatasetWidget = DatasetWidget;
})();

(function () {
    const csscls = function (cls) {
        return PhpDebugBar.utils.csscls(cls, 'phpdebugbar-openhandler-');
    };

    PhpDebugBar.OpenHandler = PhpDebugBar.Widget.extend({

        className: 'phpdebugbar-openhandler',

        defaults: {
            items_per_page: 20
        },

        render() {
            const self = this;

            document.body.append(this.el);
            this.el.style.display = 'none';

            this.closebtn = document.createElement('a');
            this.closebtn.classList.add(csscls('closebtn'));
            this.closebtn.innerHTML = '<i class="phpdebugbar-icon phpdebugbar-icon-x"></i>';

            this.brand = document.createElement('span');
            this.brand.classList.add(csscls('brand'));
            this.brand.innerHTML = '<i class="phpdebugbar-icon phpdebugbar-icon-brand"></i>';

            this.table = document.createElement('tbody');

            const header = document.createElement('div');
            header.classList.add(csscls('header'));
            header.textContent = 'PHP DebugBar | Open';
            header.prepend(this.brand);
            header.append(this.closebtn);

            this.el.append(header);

            const tableWrapper = document.createElement('table');
            tableWrapper.innerHTML = '<thead><tr><th width="155">Date</th><th width="75">Method</th><th>URL</th><th width="125">IP</th><th width="100">Filter data</th></tr></thead>';
            tableWrapper.append(this.table);
            this.el.append(tableWrapper);

            this.actions = document.createElement('div');
            this.actions.classList.add(csscls('actions'));
            this.el.append(this.actions);

            this.closebtn.addEventListener('click', () => {
                self.hide();
            });

            this.loadmorebtn = document.createElement('a');
            this.loadmorebtn.textContent = 'Load more';
            this.actions.append(this.loadmorebtn);
            this.loadmorebtn.addEventListener('click', () => {
                self.find(self.last_find_request, self.last_find_request.offset + self.get('items_per_page'), self.handleFind.bind(self));
            });

            this.showonlycurrentbtn = document.createElement('a');
            this.showonlycurrentbtn.textContent = 'Show only current URL';
            this.actions.append(this.showonlycurrentbtn);
            this.showonlycurrentbtn.addEventListener('click', () => {
                self.uriInput.value = window.location.pathname;
                self.searchBtn.click();
            });

            this.refreshbtn = document.createElement('a');
            this.refreshbtn.textContent = 'Refresh';
            this.actions.append(this.refreshbtn);
            this.refreshbtn.addEventListener('click', () => {
                self.refresh();
            });

            this.clearbtn = document.createElement('a');
            this.clearbtn.textContent = 'Clear storage';
            this.actions.append(this.clearbtn);
            this.clearbtn.addEventListener('click', () => {
                self.clear(() => {
                    self.hide();
                });
            });

            this.addSearch();

            this.overlay = document.createElement('div');
            this.overlay.classList.add(csscls('overlay'));
            this.overlay.style.display = 'none';
            document.body.append(this.overlay);
            this.overlay.addEventListener('click', () => {
                self.hide();
            });
        },

        refresh() {
            this.table.innerHTML = '';
            this.loadmorebtn.style.display = '';
            this.find({}, 0, this.handleFind.bind(this));
        },

        addSearch() {
            const self = this;

            const searchBtn = this.searchBtn = document.createElement('button');
            searchBtn.textContent = 'Search';
            searchBtn.type = 'submit';
            searchBtn.addEventListener('click', function (e) {
                self.table.innerHTML = '';
                const search = {};
                const formData = new FormData(this.parentElement);
                for (const [name, value] of formData.entries()) {
                    if (value) {
                        search[name] = value;
                    }
                }

                self.find(search, 0, self.handleFind.bind(self));
                e.preventDefault();
            });

            const form = document.createElement('form');
            form.innerHTML = '<br/><b>Filter results</b><br/>'
                + '<select name="method"><option selected value="">(Method)</option><option>GET</option><option>POST</option><option>PUT</option><option>DELETE</option></select>';

            this.uriInput = document.createElement('input');
            this.uriInput.type = 'text';
            this.uriInput.name = 'uri';
            this.uriInput.placeholder = "URI, eg '/user/*'";
            form.append(this.uriInput);

            this.ipInput = document.createElement('input');
            this.ipInput.type = 'text';
            this.ipInput.name = 'ip';
            this.ipInput.placeholder = 'IP';
            form.append(this.ipInput);

            const resetBtn = document.createElement('button');
            resetBtn.textContent = 'Reset';
            resetBtn.type = 'button';
            resetBtn.addEventListener('click', () => {
                form.reset();
                searchBtn.click();
            });
            form.append(searchBtn);
            form.append(resetBtn);
            this.actions.append(form);
        },

        handleFind(data) {
            const self = this;
            for (const meta of data) {
                const loadLink = document.createElement('a');
                loadLink.textContent = 'Load dataset';
                loadLink.addEventListener('click', (e) => {
                    self.hide();
                    self.load(meta.id, (data) => {
                        self.callback(meta.id, data);
                    });
                    e.preventDefault();
                });

                const methodLink = document.createElement('a');
                methodLink.textContent = meta.method;
                methodLink.addEventListener('click', (e) => {
                    self.table.innerHTML = '';
                    self.find({ method: meta.method }, 0, self.handleFind.bind(self));
                    e.preventDefault();
                });

                const uriLink = document.createElement('a');
                uriLink.textContent = meta.uri;
                uriLink.addEventListener('click', (e) => {
                    self.hide();
                    self.load(meta.id, (data) => {
                        self.callback(meta.id, data);
                    });
                    e.preventDefault();
                });

                const ipLink = document.createElement('a');
                ipLink.textContent = meta.ip;
                ipLink.addEventListener('click', (e) => {
                    self.ipInput.value = meta.ip;
                    self.searchBtn.click();
                    e.preventDefault();
                });

                const searchLink = document.createElement('a');
                searchLink.textContent = 'Show URL';
                searchLink.addEventListener('click', (e) => {
                    self.uriInput.value = meta.uri;
                    self.searchBtn.click();
                    e.preventDefault();
                });

                const tr = document.createElement('tr');
                const datetimeTd = document.createElement('td');
                datetimeTd.textContent = meta.datetime;
                tr.append(datetimeTd);

                const methodTd = document.createElement('td');
                methodTd.textContent = meta.method;
                tr.append(methodTd);

                const uriTd = document.createElement('td');
                uriTd.append(uriLink);
                tr.append(uriTd);

                const ipTd = document.createElement('td');
                ipTd.append(ipLink);
                tr.append(ipTd);

                const searchTd = document.createElement('td');
                searchTd.append(searchLink);
                tr.append(searchTd);

                self.table.append(tr);
            }
            if (data.length < this.get('items_per_page')) {
                this.loadmorebtn.style.display = 'none';
            }
        },

        show(callback) {
            this.callback = callback;
            this.el.style.display = 'block';
            this.overlay.style.display = 'block';
            this.refresh();
        },

        hide() {
            this.el.style.display = 'none';
            this.overlay.style.display = 'none';
        },

        find(filters, offset, callback) {
            const data = Object.assign({ op: 'find' }, filters, { max: this.get('items_per_page'), offset: offset || 0 });
            this.last_find_request = data;
            this.ajax(data, callback);
        },

        load(id, callback) {
            this.ajax({ op: 'get', id }, callback);
        },

        clear(callback) {
            this.ajax({ op: 'clear' }, callback);
        },

        ajax(data, callback) {
            let url = this.get('url');
            if (data) {
                url = url + (url.includes('?') ? '&' : '?') + new URLSearchParams(data);
            }

            fetch(url, {
                method: 'GET',
                headers: {
                    Accept: 'application/json'
                }
            })
                .then(data => data.json())
                .then(callback)
                .catch((err) => {
                    callback(null, err);
                });
        }

    });
})();

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

/* global phpdebugbar_sqlformatter */
(function () {
    const csscls = PhpDebugBar.utils.makecsscls('phpdebugbar-widgets-');

    /**
     * Widget for the displaying sql queries
     *
     * Options:
     *  - data
     */
    class SQLQueriesWidget extends PhpDebugBar.Widget {
        get className() {
            return csscls('sqlqueries');
        }

        onFilterClick(el) {
            el.classList.toggle(csscls('excluded'));
            const connection = el.getAttribute('rel');
            const items = this.list.el.querySelectorAll(`li[connection="${connection}"]`);
            for (const item of items) {
                item.hidden = !item.hidden;
            }
        }

        onCopyToClipboard(el) {
            const code = el.parentElement.querySelector('code');
            const copy = function () {
                try {
                    if (document.execCommand('copy')) {
                        el.classList.add(csscls('copy-clipboard-check'));
                        setTimeout(() => {
                            el.classList.remove(csscls('copy-clipboard-check'));
                        }, 2000);
                    }
                } catch (err) {
                    console.log('Oops, unable to copy');
                }
            };
            const select = function (node) {
                if (document.selection) {
                    const range = document.body.createTextRange();
                    range.moveToElementText(node);
                    range.select();
                } else if (window.getSelection) {
                    const range = document.createRange();
                    range.selectNodeContents(node);
                    window.getSelection().removeAllRanges();
                    window.getSelection().addRange(range);
                }
                copy();
                window.getSelection().removeAllRanges();
            };
            select(code);
        }

        renderList(table, caption, data) {
            const thead = document.createElement('thead');

            const tr = document.createElement('tr');
            const nameTh = document.createElement('th');
            nameTh.colSpan = 2;
            nameTh.classList.add(csscls('name'));
            nameTh.innerHTML = caption;
            tr.append(nameTh);
            thead.append(tr);

            table.append(thead);

            const tbody = document.createElement('tbody');

            for (const key in data) {
                const value = typeof data[key] === 'function' ? `${data[key].name} {}` : data[key];
                const tr = document.createElement('tr');

                if (typeof value === 'object' && value !== null) {
                    const keyTd = document.createElement('td');
                    keyTd.classList.add('phpdebugbar-text-muted');
                    keyTd.textContent = value.index || key;
                    tr.append(keyTd);

                    const valueTd = document.createElement('td');
                    if (value.namespace) {
                        valueTd.append(`${value.namespace}::`);
                    }

                    valueTd.append(value.name || value.file);

                    if (value.line) {
                        const lineSpan = document.createElement('span');
                        lineSpan.classList.add('phpdebugbar-text-muted');
                        lineSpan.textContent = `:${value.line}`;
                        valueTd.append(lineSpan);
                    }
                } else {
                    const keyTd = document.createElement('td');
                    keyTd.classList.add('phpdebugbar-text-muted');
                    keyTd.textContent = key;
                    tr.append(keyTd);

                    const valueTd = document.createElement('td');
                    valueTd.classList.add('phpdebugbar-text-muted');
                    valueTd.textContent = value;
                    tr.append(valueTd);
                }

                tbody.append(tr);
            }

            table.append(tbody);
        }

        itemRenderer(li, stmt) {
            stmt.type = stmt.type || 'query';
            if (stmt.slow) {
                li.classList.add(csscls('sql-slow'));
            }
            if (stmt.width_percent) {
                const bgMeasure = document.createElement('div');
                bgMeasure.classList.add(csscls('bg-measure'));

                const valueDiv = document.createElement('div');
                valueDiv.classList.add(csscls('value'));
                valueDiv.style.left = `${stmt.start_percent}%`;
                valueDiv.style.width = `${Math.max(stmt.width_percent, 0.01)}%`;
                bgMeasure.append(valueDiv);
                li.append(bgMeasure);
            }
            if (stmt.duration_str) {
                const duration = document.createElement('span');
                duration.setAttribute('title', 'Duration');
                duration.classList.add(csscls('duration'));
                duration.textContent = stmt.duration_str;
                li.append(duration);
            }
            if (stmt.memory_str) {
                const memory = document.createElement('span');
                memory.setAttribute('title', 'Memory usage');
                memory.classList.add(csscls('memory'));
                memory.textContent = stmt.memory_str;
                li.append(memory);
            }
            if (typeof (stmt.row_count) !== 'undefined') {
                const rowCount = document.createElement('span');
                rowCount.setAttribute('title', 'Row count');
                rowCount.classList.add(csscls('row-count'));
                rowCount.textContent = stmt.row_count;
                li.append(rowCount);
            }
            if (typeof (stmt.stmt_id) !== 'undefined' && stmt.stmt_id) {
                const stmtId = document.createElement('span');
                stmtId.setAttribute('title', 'Prepared statement ID');
                stmtId.classList.add(csscls('stmt-id'));
                stmtId.textContent = stmt.stmt_id;
                li.append(stmtId);
            }
            if (stmt.connection) {
                const database = document.createElement('span');
                database.setAttribute('title', 'Connection');
                database.classList.add(csscls('database'));
                database.textContent = stmt.connection;
                li.append(database);
                li.setAttribute('connection', stmt.connection);

                if (!this.filters.includes(stmt.connection)) {
                    this.filters.push(stmt.connection);

                    const filterLink = document.createElement('a');
                    filterLink.classList.add(csscls('filter'));
                    filterLink.textContent = stmt.connection;
                    filterLink.setAttribute('rel', stmt.connection);
                    filterLink.addEventListener('click', () => {
                        this.onFilterClick(filterLink);
                    });
                    this.toolbar.append(filterLink);

                    if (this.filters.length > 1) {
                        this.toolbar.hidden = false;
                    }
                }
            }
            if (stmt.type === 'query') {
                const copyBtn = document.createElement('span');
                copyBtn.setAttribute('title', 'Copy to clipboard');
                copyBtn.classList.add(csscls('copy-clipboard'));
                copyBtn.style.cursor = 'pointer';
                copyBtn.innerHTML = '&#8203;';
                copyBtn.addEventListener('click', (event) => {
                    this.onCopyToClipboard(copyBtn);
                    event.stopPropagation();
                });
                li.append(copyBtn);
            }
            if (stmt.xdebug_link) {
                li.prepend(PhpDebugBar.Widgets.editorLink(stmt.xdebug_link));
            } else if (typeof (stmt.filename) !== 'undefined' && stmt.filename) {
                li.prepend(PhpDebugBar.Widgets.editorLink(stmt));
            }
            if (stmt.type !== 'query') {
                const strong = document.createElement('strong');
                strong.classList.add(csscls('sql'), csscls(stmt.type));
                strong.textContent = stmt.sql;
                li.append(strong);
            } else {
                const code = document.createElement('code');
                code.classList.add(csscls('sql'));
                code.innerHTML = PhpDebugBar.Widgets.highlight(stmt.sql, 'sql');
                li.append(code);
            }
            if (typeof (stmt.is_success) !== 'undefined' && !stmt.is_success) {
                li.classList.add(csscls('error'));
                const errorSpan = document.createElement('span');
                errorSpan.classList.add(csscls('error'));
                errorSpan.textContent = `[${stmt.error_code}] ${stmt.error_message}`;
                li.append(errorSpan);
            }

            if (stmt.type !== 'query') {
                return;
            }

            const table = document.createElement('table');
            table.classList.add(csscls('params'));
            table.hidden = true;

            if (stmt.params && Object.keys(stmt.params).length > 0) {
                this.renderList(table, 'Params', stmt.params);
            }
            if (stmt.backtrace && Object.keys(stmt.backtrace).length > 0) {
                const values = [];
                for (const trace of stmt.backtrace.values()) {
                    let text = trace.name || trace.file;
                    if (trace.line) {
                        text = `${text}:${trace.line}`;
                    }
                    values.push(text);
                }
                this.renderList(table, 'Backtrace', values);
            }
            if (!table.querySelectorAll('tr').length) {
                table.style.display = 'none';
            }
            li.append(table);
            li.style.cursor = 'pointer';
            li.addEventListener('click', (event) => {
                if (window.getSelection().type === 'Range' || event.target.closest('.sf-dump')) {
                    return '';
                }
                table.hidden = !table.hidden;
                const code = li.querySelector('code');
                if (code && typeof phpdebugbar_sqlformatter !== 'undefined') {
                    let sql = stmt.sql;
                    if (!table.hidden) {
                        sql = phpdebugbar_sqlformatter.format(stmt.sql);
                    }
                    code.innerHTML = PhpDebugBar.Widgets.highlight(sql, 'sql');
                }
            });
        }

        render() {
            this.status = document.createElement('div');
            this.status.classList.add(csscls('status'));
            this.el.append(this.status);

            this.toolbar = document.createElement('div');
            this.toolbar.classList.add(csscls('toolbar'));
            this.el.append(this.toolbar);

            this.filters = [];
            let sortState = 'none'; // 'none', 'asc', 'desc'
            let originalData = null;

            this.list = new PhpDebugBar.Widgets.ListWidget({
                itemRenderer: (li, stmt) => this.itemRenderer(li, stmt)
            });
            this.el.append(this.list.el);

            this.bindAttr('data', function (data) {
                // the PDO collector maybe is empty
                if (data.length <= 0 || !data.statements) {
                    return false;
                }
                this.filters = [];
                this.toolbar.hidden = true;
                const toolbarFilters = this.toolbar.querySelectorAll(`.${csscls('filter')}`);
                for (const filter of toolbarFilters) {
                    filter.remove();
                }
                this.list.set('data', data.statements);
                this.status.innerHTML = '';

                // Search for duplicate statements.
                const sql = {};
                let duplicate = 0;
                for (let i = 0; i < data.statements.length; i++) {
                    if (data.statements[i].type && data.statements[i].type !== 'query') {
                        continue;
                    }
                    let stmt = data.statements[i].sql;
                    if (data.statements[i].params && Object.keys(data.statements[i].params).length > 0) {
                        stmt += JSON.stringify(data.statements[i].params);
                    }
                    if (data.statements[i].connection) {
                        stmt += `@${data.statements[i].connection}`;
                    }
                    sql[stmt] = sql[stmt] || { keys: [] };
                    sql[stmt].keys.push(i);
                }
                // Add classes to all duplicate SQL statements.
                for (const stmt in sql) {
                    if (sql[stmt].keys.length > 1) {
                        duplicate += sql[stmt].keys.length;
                        for (let i = 0; i < sql[stmt].keys.length; i++) {
                            const listItems = this.list.el.querySelectorAll(`.${csscls('list-item')}`);
                            if (listItems[sql[stmt].keys[i]]) {
                                listItems[sql[stmt].keys[i]].classList.add(csscls('sql-duplicate'));
                            }
                        }
                    }
                }

                const t = document.createElement('span');
                t.textContent = `${data.nb_statements} statements were executed`;
                if (data.nb_excluded_statements) {
                    t.textContent += `, ${data.nb_excluded_statements} have been excluded`;
                }
                this.status.append(t);

                if (data.nb_failed_statements) {
                    t.append(`, ${data.nb_failed_statements} of which failed`);
                }
                if (duplicate) {
                    t.append(`, ${duplicate} of which were duplicates`);
                    t.append(`, ${data.nb_statements - duplicate} unique. `);

                    // add toggler for displaying only duplicated queries
                    const duplicatedText = 'Show only duplicated';
                    const toggleLink = document.createElement('a');
                    toggleLink.classList.add(csscls('duplicates'));
                    toggleLink.textContent = duplicatedText;
                    toggleLink.addEventListener('click', () => {
                        toggleLink.classList.toggle('shown-duplicated');
                        toggleLink.textContent = toggleLink.classList.contains('shown-duplicated') ? 'Show All' : duplicatedText;

                        const selector = `.${this.className} .${csscls('list-item')}:not(.${csscls('sql-duplicate')})`;
                        const items = document.querySelectorAll(selector);
                        for (const item of items) {
                            item.hidden = !item.hidden;
                        }
                    });
                    t.append(toggleLink);
                }
                if (data.accumulated_duration_str) {
                    const duration = document.createElement('span');
                    duration.setAttribute('title', 'Accumulated duration');
                    duration.classList.add(csscls('duration'));
                    duration.textContent = data.accumulated_duration_str;

                    const sortIcon = document.createElement('span');
                    sortIcon.classList.add(csscls('sort-icon'));
                    sortIcon.style.cursor = 'pointer';
                    sortIcon.style.marginLeft = '5px';
                    sortIcon.textContent = 'Sort ⇅';
                    sortIcon.setAttribute('title', 'Sort by duration');

                    sortIcon.addEventListener('click', () => {
                        if (sortState === 'none') {
                            sortState = 'desc';
                            sortIcon.textContent = '↓';
                            originalData = [...data.statements];
                            data.statements.sort((a, b) => (b.duration || 0) - (a.duration || 0));
                        } else if (sortState === 'desc') {
                            sortState = 'asc';
                            sortIcon.textContent = '↑';
                            data.statements.sort((a, b) => (a.duration || 0) - (b.duration || 0));
                        } else {
                            sortState = 'none';
                            sortIcon.textContent = '⇅';
                            if (originalData) {
                                data.statements = originalData;
                                originalData = null;
                            }
                        }
                        this.list.set('data', data.statements);
                    });

                    duration.append(sortIcon);
                    this.status.append(duration);
                }
                if (data.memory_usage_str) {
                    const memory = document.createElement('span');
                    memory.setAttribute('title', 'Memory usage');
                    memory.classList.add(csscls('memory'));
                    memory.textContent = data.memory_usage_str;
                    this.status.append(memory);
                }
            });
        }
    }
    PhpDebugBar.Widgets.SQLQueriesWidget = SQLQueriesWidget;
})();

(function () {
    const csscls = PhpDebugBar.utils.makecsscls('phpdebugbar-widgets-');

    /**
     * Widget for the displaying templates data
     *
     * Options:
     *  - data
     */
    class TemplatesWidget extends PhpDebugBar.Widget {
        get className() {
            return csscls('templates');
        }

        render() {
            this.status = document.createElement('div');
            this.status.classList.add(csscls('status'));
            this.el.append(this.status);

            this.list = new PhpDebugBar.Widgets.ListWidget({ itemRenderer(li, tpl) {
                const name = document.createElement('span');
                name.classList.add(csscls('name'));
                if (tpl.html) {
                    name.innerHTML = tpl.html;
                } else {
                    name.textContent = tpl.name;
                }
                li.append(name);

                if (tpl.xdebug_link) {
                    li.append(PhpDebugBar.Widgets.editorLink(tpl.xdebug_link));
                }

                if (tpl.render_time_str) {
                    const renderTime = document.createElement('span');
                    renderTime.setAttribute('title', 'Render time');
                    renderTime.classList.add(csscls('render-time'));
                    renderTime.textContent = tpl.render_time_str;
                    li.append(renderTime);
                }
                if (tpl.memory_str) {
                    const memory = document.createElement('span');
                    memory.setAttribute('title', 'Memory usage');
                    memory.classList.add(csscls('memory'));
                    memory.textContent = tpl.memory_str;
                    li.append(memory);
                }
                if (typeof (tpl.param_count) !== 'undefined') {
                    const paramCount = document.createElement('span');
                    paramCount.setAttribute('title', 'Parameter count');
                    paramCount.classList.add(csscls('param-count'));
                    paramCount.textContent = tpl.param_count;
                    li.append(paramCount);
                }
                if (typeof (tpl.type) !== 'undefined' && tpl.type) {
                    const type = document.createElement('span');
                    type.setAttribute('title', 'Type');
                    type.classList.add(csscls('type'));
                    type.textContent = tpl.type;
                    li.append(type);
                }
                if (typeof (tpl.editorLink) !== 'undefined' && tpl.editorLink) {
                    const editorLink = document.createElement('a');
                    editorLink.setAttribute('href', tpl.editorLink);
                    editorLink.classList.add(csscls('editor-link'));
                    editorLink.textContent = 'file';
                    editorLink.addEventListener('click', (event) => {
                        event.stopPropagation();
                    });
                    li.append(editorLink);
                }
                if (tpl.params && Object.keys(tpl.params).length > 0) {
                    const table = document.createElement('table');
                    table.classList.add(csscls('params'));
                    const thead = document.createElement('thead');
                    thead.innerHTML = '<tr><th colspan="2">Params</th></tr>';
                    const tbody = document.createElement('tbody');
                    table.append(thead, tbody);

                    for (const key in tpl.params) {
                        if (typeof tpl.params[key] !== 'function') {
                            const row = document.createElement('tr');
                            const nameTd = document.createElement('td');
                            nameTd.className = csscls('name');
                            nameTd.textContent = key;
                            row.append(nameTd);

                            const valueTd = document.createElement('td');
                            valueTd.className = csscls('value');
                            PhpDebugBar.Widgets.renderValueInto(valueTd, tpl.params[key]);
                            row.append(valueTd);
                            tbody.append(row);
                        }
                    }
                    table.hidden = true;
                    li.append(table);
                    li.style.cursor = 'pointer';
                    li.addEventListener('click', (event) => {
                        if (window.getSelection().type === 'Range' || event.target.closest('.sf-dump')) {
                            return;
                        }
                        table.hidden = !table.hidden;
                    });
                }
            } });
            this.el.append(this.list.el);

            this.callgraph = document.createElement('div');
            this.callgraph.classList.add(csscls('callgraph'));
            this.el.append(this.callgraph);

            this.bindAttr('data', function (data) {
                this.list.set('data', data.templates);
                this.status.innerHTML = '';
                this.callgraph.innerHTML = '';

                const sentence = data.sentence || 'templates were rendered';
                const sentenceSpan = document.createElement('span');
                sentenceSpan.textContent = `${data.nb_templates} ${sentence}`;
                this.status.append(sentenceSpan);

                if (data.accumulated_render_time_str) {
                    const renderTime = document.createElement('span');
                    renderTime.setAttribute('title', 'Accumulated render time');
                    renderTime.classList.add(csscls('render-time'));
                    renderTime.textContent = data.accumulated_render_time_str;
                    this.status.append(renderTime);
                }
                if (data.memory_usage_str) {
                    const memory = document.createElement('span');
                    memory.setAttribute('title', 'Memory usage');
                    memory.classList.add(csscls('memory'));
                    memory.textContent = data.memory_usage_str;
                    this.status.append(memory);
                }
                if (data.nb_blocks > 0) {
                    const blocksDiv = document.createElement('div');
                    blocksDiv.textContent = `${data.nb_blocks} blocks were rendered`;
                    this.status.append(blocksDiv);
                }
                if (data.nb_macros > 0) {
                    const macrosDiv = document.createElement('div');
                    macrosDiv.textContent = `${data.nb_macros} macros were rendered`;
                    this.status.append(macrosDiv);
                }
                if (typeof data.callgraph !== 'undefined') {
                    this.callgraph.innerHTML = data.callgraph;
                }
            });
        }
    }
    PhpDebugBar.Widgets.TemplatesWidget = TemplatesWidget;
})();

(function () {
    const csscls = PhpDebugBar.utils.makecsscls('phpdebugbar-widgets-');

    /**
     * Widget for the displaying Http Events
     *
     * Options:
     *  - data
     */
    class HttpWidget extends PhpDebugBar.Widget {
        get className() {
            return csscls('httpclient');
        }

        render() {
            this.list = new PhpDebugBar.Widgets.ListWidget({ itemRenderer(li, request) {
                // Create table row structure
                const table = document.createElement('div');
                table.classList.add(csscls('request-summary'));
                table.style.display = 'flex';
                table.style.gap = '10px';
                table.style.alignItems = 'center';

                // METHOD
                const method = document.createElement('span');
                method.classList.add(csscls('method'));
                method.textContent = request.method;
                method.style.fontWeight = 'bold';
                method.style.minWidth = '60px';
                table.append(method);

                // URL
                const url = document.createElement('span');
                url.classList.add(csscls('url'));
                url.textContent = request.url;
                url.style.flex = '1';
                url.style.overflow = 'hidden';
                url.style.textOverflow = 'ellipsis';
                url.style.whiteSpace = 'nowrap';
                table.append(url);

                // STATUS
                const status = document.createElement('span');
                status.classList.add(csscls('status'));
                status.textContent = request.status;
                status.style.minWidth = '40px';
                status.style.textAlign = 'center';
                // Color code status
                if (typeof request.status === 'number') {
                    if (request.status >= 200 && request.status < 300) {
                        status.style.color = '#4caf50';
                    } else if (request.status >= 300 && request.status < 400) {
                        status.style.color = '#ff9800';
                    } else if (request.status >= 400) {
                        status.style.color = '#f44336';
                    }
                }
                table.append(status);

                // DURATION
                if (request.duration !== null && typeof request.duration !== 'undefined') {
                    const duration = document.createElement('span');
                    duration.classList.add(csscls('duration'));
                    duration.textContent = request.duration;
                    duration.style.minWidth = '60px';
                    duration.style.textAlign = 'right';
                    table.append(duration);
                }

                li.append(table);

                // Details section (expandable)
                if (request.details && Object.keys(request.details).length > 0) {
                    const paramsTable = document.createElement('table');
                    paramsTable.classList.add(csscls('params'));
                    const thead = document.createElement('thead');
                    thead.innerHTML = '<tr><th colspan="2">Details</th></tr>';
                    const tbody = document.createElement('tbody');
                    paramsTable.append(thead, tbody);

                    for (const key in request.details) {
                        if (typeof request.details[key] !== 'function') {
                            const row = document.createElement('tr');
                            const nameTd = document.createElement('td');
                            nameTd.className = csscls('name');
                            nameTd.textContent = key;
                            row.append(nameTd);

                            const valueTd = document.createElement('td');
                            valueTd.className = csscls('value');
                            PhpDebugBar.Widgets.renderValueInto(valueTd, request.details[key]);
                            row.append(valueTd);
                            tbody.append(row);
                        }
                    }
                    paramsTable.hidden = true;
                    li.append(paramsTable);
                    li.style.cursor = 'pointer';
                    li.addEventListener('click', (event) => {
                        if (window.getSelection().type === 'Range' || event.target.closest('.sf-dump')) {
                            return;
                        }
                        paramsTable.hidden = !paramsTable.hidden;
                    });
                }
            } });

            this.el.append(this.list.el);

            this.bindAttr('data', function (data) {
                this.list.set('data', data);
            });
        }
    }

    PhpDebugBar.Widgets.HttpWidget = HttpWidget;
})();

