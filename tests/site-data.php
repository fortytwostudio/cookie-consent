<?php
require __DIR__ . '/fixtures.php';
require_once $vendor . '/craftcms/cms/src/behaviors/CustomFieldBehavior.php.template';
$app = harnessApp();
use fortytwostudio\cookieconsent\elements\CookieElement;
$cookie=new CookieElement(['siteId'=>1,'cookieSiteId'=>1,'type'=>'necessary','cookieId'=>'test','domain'=>'example.test','duration'=>'Session','description'=>'Before']);
check($cookie->getSupportedSites()===[1],'Element supports extra sites');
$cookie->setAttributes(['cookieSiteId'=>2,'description'=>'After']);
check($cookie->cookieSiteId===1 && $cookie->description==='After','Native fields or unsafe owner assignment failed');
check($cookie->validate(['cookieSiteId']),'Valid cookieSiteId rejected');
$cookie->cookieSiteId=-1;
check(!$cookie->validate(['cookieSiteId']),'Negative cookieSiteId accepted');
$cookie->cookieSiteId=2;
check(!$cookie->beforeSave(true),'Mismatched site owner accepted');
echo "Element behavior: native mass assignment, protected owner, valid/invalid owner validation, and site mismatch checks passed.\n";

use fortytwostudio\cookieconsent\services\SiteData;
use fortytwostudio\cookieconsent\services\SiteContext;
check(SiteContext::resolve('b')->id===2 && SiteContext::resolve('bbbbbbbb-2222-4222-8222-bbbbbbbbbbbb')->id===2 && SiteContext::resolve(2)->id===2,'Site resolution failed');
try {SiteContext::resolve('unknown');throw new RuntimeException('Unknown site accepted');}catch(yii\web\BadRequestHttpException){}
$db=$app->getDb();
$contentColumns=['id'=>'pk','siteId'=>'integer UNIQUE NOT NULL'];
foreach(SiteData::defaults() as $key=>$value)$contentColumns[$key]='text';
$db->createCommand()->createTable(SiteData::CONTENT_TABLE,$contentColumns)->execute();
$db->createCommand()->createTable(SiteData::TRACKED_TABLE,['id'=>'pk','siteId'=>'integer UNIQUE','accepted'=>'integer NOT NULL DEFAULT 0','rejected'=>'integer NOT NULL DEFAULT 0'])->execute();
check(SiteData::getContent(2)['popupTitle']===SiteData::defaults()['popupTitle'],'Missing site defaults failed');
SiteData::saveContent(1,['popupTitle'=>'A "quoted"','id'=>900,'siteId'=>2]);
SiteData::saveContent(2,['popupTitle'=>'B']);
SiteData::initializeSite(1);
check(SiteData::getContent(1)['popupTitle']==='A "quoted"' && SiteData::getContent(2)['popupTitle']==='B','Content leaked or initialization overwrote data');
check((int)SiteData::getContent(1)['id']!==900,'Content accepted posted row id');
$db->createCommand()->insert(SiteData::TRACKED_TABLE,['siteId'=>null,'accepted'=>8,'rejected'=>2])->execute();
for($i=0;$i<3;$i++)SiteData::logConsent(1,true);
SiteData::logConsent(1,false);SiteData::logConsent(2,false);SiteData::logConsent(2,false);
check(SiteData::getLogs(1)===['accepted'=>3,'rejected'=>1,'total'=>4],'Site A counts wrong');
check(SiteData::getLogs(2)===['accepted'=>0,'rejected'=>2,'total'=>2],'Site B counts wrong');
check(SiteData::getLegacyLogs()===['accepted'=>8,'rejected'=>2,'total'=>10],'Legacy counts changed');
echo "SiteData behavior (real Yii queries on SQLite): content isolation, identity allowlist, default initialization, atomic updates, and preserved legacy totals passed.\n";
