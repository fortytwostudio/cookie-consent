import hljs from 'highlight.js';
import 'highlight.js/styles/github.css'; // or any other theme

import javascript from "highlight.js/lib/languages/javascript";
import bash from "highlight.js/lib/languages/bash";

hljs.registerLanguage("javascript", javascript);

document.querySelectorAll('pre code').forEach((block) => {
	hljs.highlightElement(block);
});
