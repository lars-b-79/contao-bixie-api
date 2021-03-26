<?php declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Client;
use pcak\BixieApi\ApiClient;

final class ApiClientTest extends TestCase
{
    public function test_login_ok(): void
    {
        $mock = new MockHandler([
            new Response(200, [], 'WebToken')
        ]);
        
        $handlerStack = HandlerStack::create($mock);
        

        $ac = ApiClient::withTestHandler($handlerStack);
        $this->assertFalse($ac->isLoggedIn()) ;
        $this->assertTrue($ac->login('test', 'test')) ;
        $this->assertTrue($ac->isLoggedIn()) ;
        
        unset($_SESSION);
    }

    public function test_login_failed(): void
    {
        $mock = new MockHandler([
            new Response(400, ['Content-Length' => 0])
        ]);
        
        $handlerStack = HandlerStack::create($mock);
        

        $ac = ApiClient::withTestHandler($handlerStack);
       
        $this->assertFalse($ac->isLoggedIn()) ;
        $this->assertFalse($ac->login('test', 'test')) ;
        $this->assertFalse($ac->isLoggedIn()) ;
        unset($_SESSION);
    }


    public function test_login_failed_500(): void
    {
        $mock = new MockHandler([
            new Response(500, ['Content-Length' => 0])
        ]);
        
        $handlerStack = HandlerStack::create($mock);
        

        $ac = ApiClient::withTestHandler($handlerStack);
        $this->assertFalse($ac->isLoggedIn()) ;
        $this->assertFalse($ac->login('test', 'test')) ;
        $this->assertFalse($ac->isLoggedIn()) ;
        unset($_SESSION);
    }


    public function test_zusagen_200(): void
    {
        $mock = new MockHandler([
            new Response(200, [], 'WebToken'),
            new Response(200, [], '{"vorname":"Klaus","name":"Kleber","username":"klaus.kleber@web.de","zusagen":[{"id":241874,"df":"Direktzusage","art":"Leistungszusage","name":"Thyssen Wohnstätten/Wohnbau - WS1 Rente","vt":"Unternehmen","lb":"01.01.2027","buf":false,"alter":{"zahlungsart":"Rente","option":false,"stichtag":"01.01.2027","garantiert":"1.295,47"},"tod":{"zahlungsart":"Rente","option":false,"stichtag":"18.03.2021","garantiert":"777,28"},"bu":{"zahlungsart":"Rente","option":false,"stichtag":"18.03.2021","garantiert":"1.230,70"},"tags":[{"name":"kein Dokumententyp","files":[{"name":"Anwartschaftsbescheinigung Ina Kleber 31.12.2015(21.06.2016).pdf","upload":"21.06.2016 09:04","link":"https://staging-account.bixie.cloud/download/9f31fa10-4c3c-49ac-81cf-4fce9fc230ef"},{"name":"Anwartschaftsbescheinigung Ina Kleber 31.12.2014(19.08.2015).pdf","upload":"19.08.2015 14:11","link":"https://staging-account.bixie.cloud/download/0e000d0b-f836-481d-a586-26d052264488"}]}]}]}')
        ]);
        
        $handlerStack = HandlerStack::create($mock);
        

        $ac = ApiClient::withTestHandler($handlerStack);
        $this->assertFalse($ac->isLoggedIn()) ;
        $this->assertTrue($ac->login('test', 'test')) ;
        $this->assertTrue($ac->isLoggedIn()) ;
        $this->assertTrue($ac->readZusagen());

        $z = $ac->getZusagen();

        $this->assertEquals(1, count($z->zusagen));
        $this->assertEquals("Direktzusage", $z->zusagen[0]->df);

        
        unset($_SESSION);
    }



    public function test_zusagen_500(): void
    {
        $mock = new MockHandler([
            new Response(200, [], 'WebToken'),
            new Response(500, [])
        ]);
        
        $handlerStack = HandlerStack::create($mock);
        

        $ac = ApiClient::withTestHandler($handlerStack);
        $this->assertFalse($ac->isLoggedIn()) ;
        $this->assertTrue($ac->login('test', 'test')) ;
        $this->assertTrue($ac->isLoggedIn()) ;
        $this->assertFalse($ac->readZusagen());

        $z = $ac->getZusagen();
        $this->assertFalse(isset($z));

          
        unset($_SESSION);
    }



    public function test_posteingang_200(): void
    {
        $mock = new MockHandler([
            new Response(200, [], 'WebToken'),
            new Response(200, [], '{"vorname":"Klaus","name":"Kleber","username":"klaus.kleber@web.de","offen":[{"id":"2441967","erstelltDatum":"28.11.2019 07:56","abgeschlossen":false,"beitraege":[{"id":"72dd5aee-9a9a-49ab-baef-042646f02ff5","text":"weitere Aufgaben spezifizieren und bearbeiten","erstellt":"28.11.2019 07:56","author":"Peter Wurst","author_username":"Peter Wurst","files":[{"name":"VA_Strizzie_Leonie.pdf","upload":"28.11.2019 07:56","link":"https://staging-account.bixie.cloud/download/f4350cf8-54e6-4d12-917a-f16756519377"}]},{"id":"ef7532a0-a923-491a-a0e5-e5f2e46d6919","text":"Auskunft für Familiengericht","erstellt":"28.11.2019 09:58","author":"Torsten","author_username":"Torsten","files":[{"name":"VA_Strizzie_Leonie.pdf","upload":"28.11.2019 07:56","link":"https://staging-account.bixie.cloud/download/f4350cf8-54e6-4d12-917a-f16756519377"}]},{"id":"7a79c5ca-d8e1-4ce4-a276-2b350040e435","text":"Download","erstellt":"07.01.2020 10:54","author":"Ina Kleber","author_username":"Ina Kleber","files":[{"name":"20191227-VAKG-LStrizzie.pdf","upload":"27.12.2019 14:12","link":"https://staging-account.bixie.cloud/download/10b4c19e-4512-48fd-8f03-8982cd022a34"},{"name":"V30-LStrizzie.pdf","upload":"27.12.2019 14:11","link":"https://staging-account.bixie.cloud/download/6753fd7f-8741-48f6-802b-f3903cb53e6a"},{"name":"V31-LStrizzie.pdf","upload":"27.12.2019 14:19","link":"https://staging-account.bixie.cloud/download/eb9334d2-ca1e-426c-a7b3-309ac0e8a167"}]},{"id":"31093313-2f23-4b17-9176-a2c1ef3aab44","text":"Aufgabe \"Download\" abgeschlossen von klaus.kleber@web.de","erstellt":"09.01.2020 08:29","author":"Peter Wurst","author_username":"Peter Wurst","files":[]},{"id":"592686a8-2831-49f0-9fa0-534bc91d4741","text":"Ergebnisse Gericht","erstellt":"15.01.2020 17:53","author":"Ina Kleber","author_username":"Ina Kleber","files":[]}],"abgeschlossenDatum":"07.01.2020 00:00"},{"id":"2441968","erstelltDatum":"08.05.2020 12:48","abgeschlossen":false,"beitraege":[{"id":"cc65b8ec-6389-43bc-9c31-0db0c6d6e526","text":"Auskunft Familiengericht","erstellt":"11.05.2020 11:39","author":"Torsten","author_username":"Torsten","files":[{"name":"VA_Serifi.pdf","upload":"08.05.2020 12:48","link":"https://staging-account.bixie.cloud/download/a0f3cac9-392d-4b50-96d0-098a531a4f70"}]},{"id":"6736ca3a-8f1a-43d3-a177-192e7d3cea16","text":"Aufgabe \"Auskunft Familiengericht\" abgeschlossen von Torsten","erstellt":"19.05.2020 15:36","author":"Peter Wurst","author_username":"Peter Wurst","files":[]},{"id":"c3a405ac-c081-4c5b-8ae0-2883d0089673","text":"Download Dokumente","erstellt":"19.05.2020 16:06","author":"Ina Kleber","author_username":"Ina Kleber","files":[{"name":"20200519-V31-SSerifi.pdf","upload":"19.05.2020 15:36","link":"https://staging-account.bixie.cloud/download/758082d0-63aa-460e-964b-ab909c5101cc"},{"name":"20200519-VAKG-SSerifi.pdf","upload":"19.05.2020 15:36","link":"https://staging-account.bixie.cloud/download/004cdb46-a6d1-45fb-a9d8-df1455378330"},{"name":"20200519-V30-SSerifi.pdf","upload":"19.05.2020 15:36","link":"https://staging-account.bixie.cloud/download/f7ca9410-c693-4ced-9b12-81ab91c1f724"}]},{"id":"6dac3d8f-add8-426d-b64a-5ac580f543b3","text":"Beschluss Familiengericht\nWarten auf Beschluss","erstellt":"20.05.2020 11:23","author":"Ina Kleber","author_username":"Ina Kleber","files":[]}],"abgeschlossenDatum":"11.05.2020 00:00"}],"geschlossen":[]}')
        ]);
        
        $handlerStack = HandlerStack::create($mock);
        

        $ac = ApiClient::withTestHandler($handlerStack);
        $this->assertFalse($ac->isLoggedIn()) ;
        $this->assertTrue($ac->login('test', 'test')) ;
        $this->assertTrue($ac->isLoggedIn()) ;
        $this->assertTrue($ac->readPosteingang());

        $p = $ac->getPosteingang();

        $this->assertEquals(2, count($p->offen));
        $this->assertEquals(0, count($p->geschlossen));

        
        unset($_SESSION);
    }



    public function test_posteingang_500(): void
    {
        $mock = new MockHandler([
            new Response(200, [], 'WebToken'),
            new Response(500, [])
        ]);
        
        $handlerStack = HandlerStack::create($mock);
        

        $ac = ApiClient::withTestHandler($handlerStack);
        $this->assertFalse($ac->isLoggedIn()) ;
        $this->assertTrue($ac->login('test', 'test')) ;
        $this->assertTrue($ac->isLoggedIn()) ;
        $this->assertFalse($ac->readPosteingang());

        $p = $ac->getPosteingang();

        $this->assertFalse(isset($p));
        
        unset($_SESSION);
    }




    public function test_openTicket_without_files_200(): void
    {
        $mock = new MockHandler([
            new Response(200, [], 'WebToken'),
            new Response(200, [], '{"vorname":"Klaus","name":"Kleber","username":"klaus.kleber@web.de","offen":[{"id":"2441967","erstelltDatum":"28.11.2019 07:56","abgeschlossen":false,"beitraege":[{"id":"72dd5aee-9a9a-49ab-baef-042646f02ff5","text":"weitere Aufgaben spezifizieren und bearbeiten","erstellt":"28.11.2019 07:56","author":"Peter Wurst","author_username":"Peter Wurst","files":[{"name":"VA_Strizzie_Leonie.pdf","upload":"28.11.2019 07:56","link":"https://staging-account.bixie.cloud/download/f4350cf8-54e6-4d12-917a-f16756519377"}]},{"id":"ef7532a0-a923-491a-a0e5-e5f2e46d6919","text":"Auskunft für Familiengericht","erstellt":"28.11.2019 09:58","author":"Torsten","author_username":"Torsten","files":[{"name":"VA_Strizzie_Leonie.pdf","upload":"28.11.2019 07:56","link":"https://staging-account.bixie.cloud/download/f4350cf8-54e6-4d12-917a-f16756519377"}]},{"id":"7a79c5ca-d8e1-4ce4-a276-2b350040e435","text":"Download","erstellt":"07.01.2020 10:54","author":"Ina Kleber","author_username":"Ina Kleber","files":[{"name":"20191227-VAKG-LStrizzie.pdf","upload":"27.12.2019 14:12","link":"https://staging-account.bixie.cloud/download/10b4c19e-4512-48fd-8f03-8982cd022a34"},{"name":"V30-LStrizzie.pdf","upload":"27.12.2019 14:11","link":"https://staging-account.bixie.cloud/download/6753fd7f-8741-48f6-802b-f3903cb53e6a"},{"name":"V31-LStrizzie.pdf","upload":"27.12.2019 14:19","link":"https://staging-account.bixie.cloud/download/eb9334d2-ca1e-426c-a7b3-309ac0e8a167"}]},{"id":"31093313-2f23-4b17-9176-a2c1ef3aab44","text":"Aufgabe \"Download\" abgeschlossen von klaus.kleber@web.de","erstellt":"09.01.2020 08:29","author":"Peter Wurst","author_username":"Peter Wurst","files":[]},{"id":"592686a8-2831-49f0-9fa0-534bc91d4741","text":"Ergebnisse Gericht","erstellt":"15.01.2020 17:53","author":"Ina Kleber","author_username":"Ina Kleber","files":[]}],"abgeschlossenDatum":"07.01.2020 00:00"},{"id":"2441968","erstelltDatum":"08.05.2020 12:48","abgeschlossen":false,"beitraege":[{"id":"cc65b8ec-6389-43bc-9c31-0db0c6d6e526","text":"Auskunft Familiengericht","erstellt":"11.05.2020 11:39","author":"Torsten","author_username":"Torsten","files":[{"name":"VA_Serifi.pdf","upload":"08.05.2020 12:48","link":"https://staging-account.bixie.cloud/download/a0f3cac9-392d-4b50-96d0-098a531a4f70"}]},{"id":"6736ca3a-8f1a-43d3-a177-192e7d3cea16","text":"Aufgabe \"Auskunft Familiengericht\" abgeschlossen von Torsten","erstellt":"19.05.2020 15:36","author":"Peter Wurst","author_username":"Peter Wurst","files":[]},{"id":"c3a405ac-c081-4c5b-8ae0-2883d0089673","text":"Download Dokumente","erstellt":"19.05.2020 16:06","author":"Ina Kleber","author_username":"Ina Kleber","files":[{"name":"20200519-V31-SSerifi.pdf","upload":"19.05.2020 15:36","link":"https://staging-account.bixie.cloud/download/758082d0-63aa-460e-964b-ab909c5101cc"},{"name":"20200519-VAKG-SSerifi.pdf","upload":"19.05.2020 15:36","link":"https://staging-account.bixie.cloud/download/004cdb46-a6d1-45fb-a9d8-df1455378330"},{"name":"20200519-V30-SSerifi.pdf","upload":"19.05.2020 15:36","link":"https://staging-account.bixie.cloud/download/f7ca9410-c693-4ced-9b12-81ab91c1f724"}]},{"id":"6dac3d8f-add8-426d-b64a-5ac580f543b3","text":"Beschluss Familiengericht\nWarten auf Beschluss","erstellt":"20.05.2020 11:23","author":"Ina Kleber","author_username":"Ina Kleber","files":[]}],"abgeschlossenDatum":"11.05.2020 00:00"}],"geschlossen":[]}')
        ]);
        
        $handlerStack = HandlerStack::create($mock);
        

        $ac = ApiClient::withTestHandler($handlerStack);
        $this->assertFalse($ac->isLoggedIn()) ;
        $this->assertTrue($ac->login('test', 'test')) ;
        $this->assertTrue($ac->isLoggedIn()) ;
        $this->assertTrue($ac->openTicket("Betreff", "Hallo"));

        $p = $ac->getPosteingang();

        $this->assertEquals(2, count($p->offen));
        $this->assertEquals(0, count($p->geschlossen));

        
        unset($_SESSION);
    }



    public function test_openTicket_with_files_200(): void
    {
        $mock = new MockHandler([
            new Response(200, [], 'WebToken'),
            new Response(200, [], '{"vorname":"Klaus","name":"Kleber","username":"klaus.kleber@web.de","offen":[{"id":"2441967","erstelltDatum":"28.11.2019 07:56","abgeschlossen":false,"beitraege":[{"id":"72dd5aee-9a9a-49ab-baef-042646f02ff5","text":"weitere Aufgaben spezifizieren und bearbeiten","erstellt":"28.11.2019 07:56","author":"Peter Wurst","author_username":"Peter Wurst","files":[{"name":"VA_Strizzie_Leonie.pdf","upload":"28.11.2019 07:56","link":"https://staging-account.bixie.cloud/download/f4350cf8-54e6-4d12-917a-f16756519377"}]},{"id":"ef7532a0-a923-491a-a0e5-e5f2e46d6919","text":"Auskunft für Familiengericht","erstellt":"28.11.2019 09:58","author":"Torsten","author_username":"Torsten","files":[{"name":"VA_Strizzie_Leonie.pdf","upload":"28.11.2019 07:56","link":"https://staging-account.bixie.cloud/download/f4350cf8-54e6-4d12-917a-f16756519377"}]},{"id":"7a79c5ca-d8e1-4ce4-a276-2b350040e435","text":"Download","erstellt":"07.01.2020 10:54","author":"Ina Kleber","author_username":"Ina Kleber","files":[{"name":"20191227-VAKG-LStrizzie.pdf","upload":"27.12.2019 14:12","link":"https://staging-account.bixie.cloud/download/10b4c19e-4512-48fd-8f03-8982cd022a34"},{"name":"V30-LStrizzie.pdf","upload":"27.12.2019 14:11","link":"https://staging-account.bixie.cloud/download/6753fd7f-8741-48f6-802b-f3903cb53e6a"},{"name":"V31-LStrizzie.pdf","upload":"27.12.2019 14:19","link":"https://staging-account.bixie.cloud/download/eb9334d2-ca1e-426c-a7b3-309ac0e8a167"}]},{"id":"31093313-2f23-4b17-9176-a2c1ef3aab44","text":"Aufgabe \"Download\" abgeschlossen von klaus.kleber@web.de","erstellt":"09.01.2020 08:29","author":"Peter Wurst","author_username":"Peter Wurst","files":[]},{"id":"592686a8-2831-49f0-9fa0-534bc91d4741","text":"Ergebnisse Gericht","erstellt":"15.01.2020 17:53","author":"Ina Kleber","author_username":"Ina Kleber","files":[]}],"abgeschlossenDatum":"07.01.2020 00:00"},{"id":"2441968","erstelltDatum":"08.05.2020 12:48","abgeschlossen":false,"beitraege":[{"id":"cc65b8ec-6389-43bc-9c31-0db0c6d6e526","text":"Auskunft Familiengericht","erstellt":"11.05.2020 11:39","author":"Torsten","author_username":"Torsten","files":[{"name":"VA_Serifi.pdf","upload":"08.05.2020 12:48","link":"https://staging-account.bixie.cloud/download/a0f3cac9-392d-4b50-96d0-098a531a4f70"}]},{"id":"6736ca3a-8f1a-43d3-a177-192e7d3cea16","text":"Aufgabe \"Auskunft Familiengericht\" abgeschlossen von Torsten","erstellt":"19.05.2020 15:36","author":"Peter Wurst","author_username":"Peter Wurst","files":[]},{"id":"c3a405ac-c081-4c5b-8ae0-2883d0089673","text":"Download Dokumente","erstellt":"19.05.2020 16:06","author":"Ina Kleber","author_username":"Ina Kleber","files":[{"name":"20200519-V31-SSerifi.pdf","upload":"19.05.2020 15:36","link":"https://staging-account.bixie.cloud/download/758082d0-63aa-460e-964b-ab909c5101cc"},{"name":"20200519-VAKG-SSerifi.pdf","upload":"19.05.2020 15:36","link":"https://staging-account.bixie.cloud/download/004cdb46-a6d1-45fb-a9d8-df1455378330"},{"name":"20200519-V30-SSerifi.pdf","upload":"19.05.2020 15:36","link":"https://staging-account.bixie.cloud/download/f7ca9410-c693-4ced-9b12-81ab91c1f724"}]},{"id":"6dac3d8f-add8-426d-b64a-5ac580f543b3","text":"Beschluss Familiengericht\nWarten auf Beschluss","erstellt":"20.05.2020 11:23","author":"Ina Kleber","author_username":"Ina Kleber","files":[]}],"abgeschlossenDatum":"11.05.2020 00:00"}],"geschlossen":[]}')
        ]);
        
        $handlerStack = HandlerStack::create($mock);
        

        $ac = ApiClient::withTestHandler($handlerStack);
        $this->assertFalse($ac->isLoggedIn()) ;
        $this->assertTrue($ac->login('test', 'test')) ;
        $this->assertTrue($ac->isLoggedIn()) ;


        $upload = tmpfile();
        $path = stream_get_meta_data($upload)['uri'];
      
        $this->assertTrue($ac->openTicket("Betreff", "Hallo", [ ['name' => 'test.txt','path' => $path] ]));

        $p = $ac->getPosteingang();

        $this->assertEquals(2, count($p->offen));
        $this->assertEquals(0, count($p->geschlossen));

        
        unset($_SESSION);
    }



    public function test_postBeitrag_without_files_200(): void
    {
        $mock = new MockHandler([
            new Response(200, [], 'WebToken'),
            new Response(200, [], '{"vorname":"Klaus","name":"Kleber","username":"klaus.kleber@web.de","offen":[{"id":"2441967","erstelltDatum":"28.11.2019 07:56","abgeschlossen":false,"beitraege":[{"id":"72dd5aee-9a9a-49ab-baef-042646f02ff5","text":"weitere Aufgaben spezifizieren und bearbeiten","erstellt":"28.11.2019 07:56","author":"Peter Wurst","author_username":"Peter Wurst","files":[{"name":"VA_Strizzie_Leonie.pdf","upload":"28.11.2019 07:56","link":"https://staging-account.bixie.cloud/download/f4350cf8-54e6-4d12-917a-f16756519377"}]},{"id":"ef7532a0-a923-491a-a0e5-e5f2e46d6919","text":"Auskunft für Familiengericht","erstellt":"28.11.2019 09:58","author":"Torsten","author_username":"Torsten","files":[{"name":"VA_Strizzie_Leonie.pdf","upload":"28.11.2019 07:56","link":"https://staging-account.bixie.cloud/download/f4350cf8-54e6-4d12-917a-f16756519377"}]},{"id":"7a79c5ca-d8e1-4ce4-a276-2b350040e435","text":"Download","erstellt":"07.01.2020 10:54","author":"Ina Kleber","author_username":"Ina Kleber","files":[{"name":"20191227-VAKG-LStrizzie.pdf","upload":"27.12.2019 14:12","link":"https://staging-account.bixie.cloud/download/10b4c19e-4512-48fd-8f03-8982cd022a34"},{"name":"V30-LStrizzie.pdf","upload":"27.12.2019 14:11","link":"https://staging-account.bixie.cloud/download/6753fd7f-8741-48f6-802b-f3903cb53e6a"},{"name":"V31-LStrizzie.pdf","upload":"27.12.2019 14:19","link":"https://staging-account.bixie.cloud/download/eb9334d2-ca1e-426c-a7b3-309ac0e8a167"}]},{"id":"31093313-2f23-4b17-9176-a2c1ef3aab44","text":"Aufgabe \"Download\" abgeschlossen von klaus.kleber@web.de","erstellt":"09.01.2020 08:29","author":"Peter Wurst","author_username":"Peter Wurst","files":[]},{"id":"592686a8-2831-49f0-9fa0-534bc91d4741","text":"Ergebnisse Gericht","erstellt":"15.01.2020 17:53","author":"Ina Kleber","author_username":"Ina Kleber","files":[]}],"abgeschlossenDatum":"07.01.2020 00:00"},{"id":"2441968","erstelltDatum":"08.05.2020 12:48","abgeschlossen":false,"beitraege":[{"id":"cc65b8ec-6389-43bc-9c31-0db0c6d6e526","text":"Auskunft Familiengericht","erstellt":"11.05.2020 11:39","author":"Torsten","author_username":"Torsten","files":[{"name":"VA_Serifi.pdf","upload":"08.05.2020 12:48","link":"https://staging-account.bixie.cloud/download/a0f3cac9-392d-4b50-96d0-098a531a4f70"}]},{"id":"6736ca3a-8f1a-43d3-a177-192e7d3cea16","text":"Aufgabe \"Auskunft Familiengericht\" abgeschlossen von Torsten","erstellt":"19.05.2020 15:36","author":"Peter Wurst","author_username":"Peter Wurst","files":[]},{"id":"c3a405ac-c081-4c5b-8ae0-2883d0089673","text":"Download Dokumente","erstellt":"19.05.2020 16:06","author":"Ina Kleber","author_username":"Ina Kleber","files":[{"name":"20200519-V31-SSerifi.pdf","upload":"19.05.2020 15:36","link":"https://staging-account.bixie.cloud/download/758082d0-63aa-460e-964b-ab909c5101cc"},{"name":"20200519-VAKG-SSerifi.pdf","upload":"19.05.2020 15:36","link":"https://staging-account.bixie.cloud/download/004cdb46-a6d1-45fb-a9d8-df1455378330"},{"name":"20200519-V30-SSerifi.pdf","upload":"19.05.2020 15:36","link":"https://staging-account.bixie.cloud/download/f7ca9410-c693-4ced-9b12-81ab91c1f724"}]},{"id":"6dac3d8f-add8-426d-b64a-5ac580f543b3","text":"Beschluss Familiengericht\nWarten auf Beschluss","erstellt":"20.05.2020 11:23","author":"Ina Kleber","author_username":"Ina Kleber","files":[]}],"abgeschlossenDatum":"11.05.2020 00:00"}],"geschlossen":[]}')
        ]);
        
        $handlerStack = HandlerStack::create($mock);
        

        $ac = ApiClient::withTestHandler($handlerStack);
        $this->assertFalse($ac->isLoggedIn()) ;
        $this->assertTrue($ac->login('test', 'test')) ;
        $this->assertTrue($ac->isLoggedIn()) ;
        $this->assertTrue($ac->postBeitrag("2441967", "Hallo"));

        $p = $ac->getPosteingang();

        $this->assertEquals(2, count($p->offen));
        $this->assertEquals(0, count($p->geschlossen));

        
        unset($_SESSION);
    }



    public function test_openTicket_without_files_500(): void
    {
        $mock = new MockHandler([
            new Response(200, [], 'WebToken'),
            new Response(500, [])
        ]);
        
        $handlerStack = HandlerStack::create($mock);
        

        $ac = ApiClient::withTestHandler($handlerStack);
        $this->assertFalse($ac->isLoggedIn()) ;
        $this->assertTrue($ac->login('test', 'test')) ;
        $this->assertTrue($ac->isLoggedIn()) ;
        $this->assertFalse($ac->openTicket("Betreff", "Hallo"));

        $p = $ac->getPosteingang();

        $this->assertFalse(isset($p));

        
        unset($_SESSION);
    }



    public function test_postBeitrag_without_files_500(): void
    {
        $mock = new MockHandler([
            new Response(200, [], 'WebToken'),
            new Response(500, [])
        ]);
        
        $handlerStack = HandlerStack::create($mock);
        

        $ac = ApiClient::withTestHandler($handlerStack);
        $this->assertFalse($ac->isLoggedIn()) ;
        $this->assertTrue($ac->login('test', 'test')) ;
        $this->assertTrue($ac->isLoggedIn()) ;
        $this->assertFalse($ac->postBeitrag("2441967", "Hallo"));

        $p = $ac->getPosteingang();

        $this->assertFalse(isset($p));

        
        unset($_SESSION);
    }



    public function test_postBeitrag_without_files_400(): void
    {
        $mock = new MockHandler([
            new Response(200, [], 'WebToken'),
            new Response(400, [])
        ]);
        
        $handlerStack = HandlerStack::create($mock);
        

        $ac = ApiClient::withTestHandler($handlerStack);
        $this->assertFalse($ac->isLoggedIn()) ;
        $this->assertTrue($ac->login('test', 'test')) ;
        $this->assertTrue($ac->isLoggedIn()) ;
        $this->assertFalse($ac->postBeitrag("2441967", "Hallo"));

        $p = $ac->getPosteingang();

        $this->assertFalse(isset($p));

        
        unset($_SESSION);
    }



    public function test_postBeitrag_with_files_200(): void
    {
        $mock = new MockHandler([
            new Response(200, [], 'WebToken'),
            new Response(200, [], '{"vorname":"Klaus","name":"Kleber","username":"klaus.kleber@web.de","offen":[{"id":"2441967","erstelltDatum":"28.11.2019 07:56","abgeschlossen":false,"beitraege":[{"id":"72dd5aee-9a9a-49ab-baef-042646f02ff5","text":"weitere Aufgaben spezifizieren und bearbeiten","erstellt":"28.11.2019 07:56","author":"Peter Wurst","author_username":"Peter Wurst","files":[{"name":"VA_Strizzie_Leonie.pdf","upload":"28.11.2019 07:56","link":"https://staging-account.bixie.cloud/download/f4350cf8-54e6-4d12-917a-f16756519377"}]},{"id":"ef7532a0-a923-491a-a0e5-e5f2e46d6919","text":"Auskunft für Familiengericht","erstellt":"28.11.2019 09:58","author":"Torsten","author_username":"Torsten","files":[{"name":"VA_Strizzie_Leonie.pdf","upload":"28.11.2019 07:56","link":"https://staging-account.bixie.cloud/download/f4350cf8-54e6-4d12-917a-f16756519377"}]},{"id":"7a79c5ca-d8e1-4ce4-a276-2b350040e435","text":"Download","erstellt":"07.01.2020 10:54","author":"Ina Kleber","author_username":"Ina Kleber","files":[{"name":"20191227-VAKG-LStrizzie.pdf","upload":"27.12.2019 14:12","link":"https://staging-account.bixie.cloud/download/10b4c19e-4512-48fd-8f03-8982cd022a34"},{"name":"V30-LStrizzie.pdf","upload":"27.12.2019 14:11","link":"https://staging-account.bixie.cloud/download/6753fd7f-8741-48f6-802b-f3903cb53e6a"},{"name":"V31-LStrizzie.pdf","upload":"27.12.2019 14:19","link":"https://staging-account.bixie.cloud/download/eb9334d2-ca1e-426c-a7b3-309ac0e8a167"}]},{"id":"31093313-2f23-4b17-9176-a2c1ef3aab44","text":"Aufgabe \"Download\" abgeschlossen von klaus.kleber@web.de","erstellt":"09.01.2020 08:29","author":"Peter Wurst","author_username":"Peter Wurst","files":[]},{"id":"592686a8-2831-49f0-9fa0-534bc91d4741","text":"Ergebnisse Gericht","erstellt":"15.01.2020 17:53","author":"Ina Kleber","author_username":"Ina Kleber","files":[]}],"abgeschlossenDatum":"07.01.2020 00:00"},{"id":"2441968","erstelltDatum":"08.05.2020 12:48","abgeschlossen":false,"beitraege":[{"id":"cc65b8ec-6389-43bc-9c31-0db0c6d6e526","text":"Auskunft Familiengericht","erstellt":"11.05.2020 11:39","author":"Torsten","author_username":"Torsten","files":[{"name":"VA_Serifi.pdf","upload":"08.05.2020 12:48","link":"https://staging-account.bixie.cloud/download/a0f3cac9-392d-4b50-96d0-098a531a4f70"}]},{"id":"6736ca3a-8f1a-43d3-a177-192e7d3cea16","text":"Aufgabe \"Auskunft Familiengericht\" abgeschlossen von Torsten","erstellt":"19.05.2020 15:36","author":"Peter Wurst","author_username":"Peter Wurst","files":[]},{"id":"c3a405ac-c081-4c5b-8ae0-2883d0089673","text":"Download Dokumente","erstellt":"19.05.2020 16:06","author":"Ina Kleber","author_username":"Ina Kleber","files":[{"name":"20200519-V31-SSerifi.pdf","upload":"19.05.2020 15:36","link":"https://staging-account.bixie.cloud/download/758082d0-63aa-460e-964b-ab909c5101cc"},{"name":"20200519-VAKG-SSerifi.pdf","upload":"19.05.2020 15:36","link":"https://staging-account.bixie.cloud/download/004cdb46-a6d1-45fb-a9d8-df1455378330"},{"name":"20200519-V30-SSerifi.pdf","upload":"19.05.2020 15:36","link":"https://staging-account.bixie.cloud/download/f7ca9410-c693-4ced-9b12-81ab91c1f724"}]},{"id":"6dac3d8f-add8-426d-b64a-5ac580f543b3","text":"Beschluss Familiengericht\nWarten auf Beschluss","erstellt":"20.05.2020 11:23","author":"Ina Kleber","author_username":"Ina Kleber","files":[]}],"abgeschlossenDatum":"11.05.2020 00:00"}],"geschlossen":[]}')
        ]);
        
        $handlerStack = HandlerStack::create($mock);
        

        $ac = ApiClient::withTestHandler($handlerStack);
        $this->assertFalse($ac->isLoggedIn()) ;
        $this->assertTrue($ac->login('test', 'test')) ;
        $this->assertTrue($ac->isLoggedIn()) ;


        $upload = tmpfile();
        $path = stream_get_meta_data($upload)['uri'];
      

        $this->assertTrue($ac->postBeitrag("2441967", "Hallo", [ ['name' => 'test.txt','path' => $path] ]));

        $p = $ac->getPosteingang();

        $this->assertEquals(2, count($p->offen));
        $this->assertEquals(0, count($p->geschlossen));

        fclose($upload);
        unset($_SESSION);
    }
}
