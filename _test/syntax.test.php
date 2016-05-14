<?php
/*
 * Copyright (c) 2016 Mark C. Prins <mprins@users.sf.net>
 *
 * Permission to use, copy, modify, and distribute this software for any
 * purpose with or without fee is hereby granted, provided that the above
 * copyright notice and this permission notice appear in all copies.
 *
 * THE SOFTWARE IS PROVIDED "AS IS" AND THE AUTHOR DISCLAIMS ALL WARRANTIES
 * WITH REGARD TO THIS SOFTWARE INCLUDING ALL IMPLIED WARRANTIES OF
 * MERCHANTABILITY AND FITNESS. IN NO EVENT SHALL THE AUTHOR BE LIABLE FOR
 * ANY SPECIAL, DIRECT, INDIRECT, OR CONSEQUENTIAL DAMAGES OR ANY DAMAGES
 * WHATSOEVER RESULTING FROM LOSS OF USE, DATA OR PROFITS, WHETHER IN AN
 * ACTION OF CONTRACT, NEGLIGENCE OR OTHER TORTIOUS ACTION, ARISING OUT OF
 * OR IN CONNECTION WITH THE USE OR PERFORMANCE OF THIS SOFTWARE.
 */

/**
 * Syntax tests for the backlinks plugin.
 *
 * @group plugin_backlinks
 * @group plugins
 */
class syntax_plugin_backlinks_test extends DokuWikiTest {

    protected $pluginsEnabled = array('backlinks');

    /**
     * copy data and add pages to the index.
     */
    public static function setUpBeforeClass(){
        parent::setUpBeforeClass();
        global $conf;

        TestUtils::rcopy(TMP_DIR, dirname(__FILE__) . '/data/');

        dbglog(scandir(DOKU_TMP_DATA.'index/'),"Index dir (after copy)");
        dbglog(scandir(DOKU_TMP_DATA.'pages/'),"Pages dir (before test)");
    }

    function setUp() {
        global $conf;
        //$this->pluginsEnabled[] = 'backlinks';
        parent::setUp();


        $conf['allowdebug'] = 1;
        $conf['cachetime'] = -1;

        $data = array();
        search($data, $conf['datadir'], 'search_allpages', array('skipacl' => true));

        dbglog($data, "pages for indexing");

        $verbose = false;
        $force = false;
        //foreach($data as $val) {
        //    idx_addPage($val['id'], $verbose, $force);
        //}
        idx_addPage('wiki:dokuwiki', $verbose, $force);
        idx_addPage('bob_ross_says', $verbose, $force);
        idx_addPage('link', $verbose, $force);
        idx_addPage('backlinks_syntax', $verbose, $force);
    }

    public function tearDown() {
        global $conf;
        parent::tearDown();

        // try to get the debug log

        //$log = file_get_contents($conf['cachedir'].'/debug.log');
        $log = file_get_contents(DOKU_TMP_DATA.'/cache/debug.log');
        if(!$log) {
            print_r($log);
            unlink($conf['cachedir'].'/debug.log');
        }
    }

    public function testIndex() {
        $indexer = idx_get_indexer();
        $query = array('Ross');

        print_r(idx_lookup($query));

        $this->assertEquals(
                 array('Ross' => array('link' => 1)),
                 idx_lookup($query)
        );

    }

    public function testDokuWikiPage() {
        $request = new TestRequest();
        $response = $request->get(array('id'=>'wiki:dokuwiki'), '/doku.php');
        $this->assertTrue(
            strpos($response->getContent(), 'DokuWiki') !== false,
            'DokuWiki was not a word in the output'
        );
    }

    public function testLinksPage() {
        $request = new TestRequest();
        $response = $request->get(array('id'=>'link'), '/doku.php');

        $this->assertTrue(
            strpos($response->getContent(), 'A link to Bob Ross') !== false,
            'A link to Bob Ross was not in the output'
        );
     }

    public function testStoryPage() {
        $request = new TestRequest();
        $response = $request->get(array('id'=>'bob_ross_says'), '/doku.php');

        $this->assertTrue(
            strpos($response->getContent(), 'Bob Ross says') !== false,
            'Bob Ross says was not in the output'
        );
    }

    public function testBacklinks() {
        $request = new TestRequest();
        $response = $request->get(array('id'=>'backlinks_syntax'), '/doku.php');

        $this->assertTrue(
            strpos($response->getContent(), 'Backlinks to what Bob Ross says') !== false,
            '"Backlinks to what Bob Ross says" was not in the output'
        );

        $doc = phpQuery::newDocument($response->getContent());
        //look for id="plugin__backlinks"
        $this->assertEquals(1, pq('#plugin__backlinks', $doc)->length,
                            'There should be one backlinks element');

        $wikilink = pq('.idx .li .wikilink1', $doc);
        $this->assertEquals(4, $wikilink->length, 'There should be 4 backlinks');

        dbglog(pq($wikilink->contents()[0], $doc),"wikilink[0]");
        $this->assertEquals($wikilink->contents()[3],'A link to Bob Ross',
                           'The last backlink should be a link to Bob Ross');
    }
}
