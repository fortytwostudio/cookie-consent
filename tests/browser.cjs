const assert = require('node:assert/strict');
const http = require('node:http');
const fs = require('node:fs');
const path = require('node:path');
const root = process.env.COOKIE_PLUGIN_PATH || path.resolve(__dirname, '..');
const {chromium} = require(path.join(root, 'node_modules/playwright'));
// Static two-site fixture around the real compiled bundle, with a mock log endpoint.
const cookieNames = {1:'cc_cookie_aaaaaaaa111141118111aaaaaaaaaaaa', 2:'cc_cookie_bbbbbbbb222242228222bbbbbbbbbbbb'};
const calls = [];
const errors = [];
const labels = {acceptAll:'Accept all',rejectAll:'Reject all',managePreferences:'Manage preferences',rejectAllAndClose:'Reject all and close',savePreferences:'Save preferences',showPreferences:'Show cookie preferences modal',necessary:'Necessary cookies',functionality:'Functional cookies',analytics:'Analytics',performance:'Performance cookies',advertisement:'Advertisement cookies',security:'Security cookies',cookie:'Cookie',domain:'Domain',description:'Description'};
function configuration(siteId) {
  const suffix = siteId === 1 ? 'a' : 'b';
  return {
    actionUrl:'/index.php?action=forty-cookieconsent/cookies/log-consent',siteId,language:siteId===1?'en-GB':'cy',labels,
    enabled:true,cookieName:cookieNames[siteId],cookiePath:'/',cookieIcon:null,
    title:'Site '+suffix.toUpperCase()+' "quoted" & private', description:'A backslash \\ and a newline\nremain safe.',footer:'<a href="/'+suffix+'/privacy">Privacy</a>',
    consentLayout:'box inline',consentPosition:'bottom right',consentEqualWeightButtons:true,consentFlipButtons:false,
    internalTitle:'Preferences for '+suffix.toUpperCase(),internalDescription:'Only this site.',
    requiredDescription:'Required on this site',functionalDescription:'Functional',analyticsDescription:'Analytics',performanceDescription:'Performance',advertisementDescription:'Ads',securityDescription:'Security',
    preferencesLayout:'bar',preferencesPosition:'right',preferencesEqualWeightButtons:true,preferencesFlipButtons:false,triggerPosition:'left',sendGtag:false,
    requiredCookies:[{cookieId:'session_'+suffix,domain:'127.0.0.1',description:'Only site '+suffix,duration:'Session'}],functionalCookies:[],analyticsCookies:[],performanceCookies:[],advertisementCookies:[],securityCookies:[],
    colours:{popup:{backgroundColour:siteId===1?'#ffffff':'#ffffdd',textColour:'#000000',buttonBackground:'#30363c',hoverButtonBackground:'#30363c',buttonText:'#ffffff',hoverButtonText:'#ffffff',manageBackground:'#eaeff2',hoverManageBackground:'#eaeff2',manageText:'#2c2f31',hoverManageText:'#2c2f31',footerBackground:'#eaeff2',footerText:'#5e6266',hoverFooterText:'#5e6266'},modal:{toggleBackground:'#f0f4f7',hoverToggleBackground:'#e9eff4',toggleText:'#30363c',hoverToggleText:'#30363c'},trigger:{backgroundColour:'#000000',fillColour:'#ffffff'}}
  };
}
const server=http.createServer((req,res)=>{
  const url=new URL(req.url,'http://localhost');
  if(url.pathname==='/index.php' && req.method==='POST') {
    let body='';req.on('data',c=>body+=c);req.on('end',()=>{calls.push(JSON.parse(body));res.setHeader('Content-Type','application/json');res.end('{"success":true}');});return;
  }
  if(url.pathname==='/main.js'||url.pathname==='/main.css') {
    res.setHeader('Content-Type',url.pathname.endsWith('.js')?'application/javascript':'text/css');
    res.end(fs.readFileSync(root+'/src/resources/dist/'+(url.pathname.endsWith('.js')?'js':'css')+url.pathname));return;
  }
  const config=configuration(url.pathname.startsWith('/b/')?2:1);
  const json=JSON.stringify(config).replace(/</g,'\\u003c').replace(/>/g,'\\u003e').replace(/&/g,'\\u0026');
  res.setHeader('Content-Type','text/html');res.end('<!doctype html><html><head><meta charset="utf-8"><link rel="stylesheet" href="/main.css"><script>window.FortyCookieConsent='+json+';</script></head><body><h1>Fixture</h1><script type="module" src="/main.js"></script></body></html>');
});
(async()=>{
  await new Promise(resolve=>server.listen(0,'127.0.0.1',resolve));
  const origin='http://127.0.0.1:'+server.address().port;
  const browser=await chromium.launch({headless:true,executablePath:process.env.CHROMIUM_PATH || undefined});
  try {
    const context=await browser.newContext({userAgent:'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36'});
    await context.addInitScript(()=>Object.defineProperty(navigator,'webdriver',{get:()=>false}));
    const page=await context.newPage();
    page.on('pageerror',e=>errors.push(e.message));
    page.on('console',m=>{if(m.type()==='error') errors.push(m.text());});
    await page.goto(origin+'/a/');
    await page.locator('.cm--bar, .cm').waitFor({state:'visible'});
    assert.equal(await page.locator('.cm__title').textContent(),'Site A "quoted" & private');
    assert.equal(await page.evaluate(()=>getComputedStyle(document.documentElement).getPropertyValue('--cc-bg').trim()),'#ffffff');
    await Promise.all([page.waitForResponse(r=>r.url().includes('log-consent')),page.getByRole('button',{name:'Accept all',exact:true}).first().click()]);
    let cookies=await context.cookies();
    assert(cookies.some(c=>c.name===cookieNames[1]));
    assert(!cookies.some(c=>c.name===cookieNames[2]));
    assert.equal(calls.length,1);assert.equal(calls[0].siteId,1);assert.equal(calls[0].acceptType,'all');
    await page.goto(origin+'/b/');
    await page.locator('.cm').waitFor({state:'visible'});
    assert.equal(await page.locator('.cm__title').textContent(),'Site B "quoted" & private');
    assert.equal(await page.evaluate(()=>getComputedStyle(document.documentElement).getPropertyValue('--cc-bg').trim()),'#ffffdd');
    await Promise.all([page.waitForResponse(r=>r.url().includes('log-consent')),page.getByRole('button',{name:'Reject all',exact:true}).first().click()]);
    cookies=await context.cookies();assert(cookies.some(c=>c.name===cookieNames[1]));assert(cookies.some(c=>c.name===cookieNames[2]));
    assert.equal(calls.length,2);assert.equal(calls[1].siteId,2);assert.equal(calls[1].acceptType,'necessary');
    await page.goto(origin+'/a/');
    await page.locator('#forty-cookie-modal-button').waitFor({state:'visible'});
    assert.equal(await page.locator('.cm').isVisible(),false);
    await page.getByRole('button',{name:'Show cookie preferences modal'}).click();
    await page.locator('.pm').waitFor({state:'visible'});
    assert.equal(await page.locator('.pm__title').textContent(),'Preferences for A');
    assert((await page.locator('.pm').textContent()).includes('session_a'));
    assert(!(await page.locator('.pm').textContent()).includes('session_b'));
    assert.equal(await page.evaluate(()=>window.dataLayer.length),0);
    assert.deepEqual(errors,[]);
    console.log('Browser checks passed: two path sites isolate consent cookies, correct content/colors/catalog, quotes/newlines valid, logging payload site IDs, consent remembered, Google disabled.');
    await context.close();
  } finally {await browser.close();server.close();}
})().catch(error=>{console.error(error);server.close();process.exitCode=1;});
