<?php
/**!info**
{
  "Plugin Name"  : "Floodlight",
  "Plugin URI"   : "http://enanocms.org/plugin/floodlight",
  "Description"  : "A much broader search than Spotlight. Adds auto-completion to the Search sidebar block.",
  "Author"       : "Dan Fuhry",
  "Version"      : "1.1.5",
  "Author URI"   : "http://enanocms.org/"
}
**!*/

$plugins->attachHook('sidebar_fetch_return', 'floodlight_inject_searchflags($return);');
$plugins->attachHook('autofill_json_request', 'floodlight_perform_search($dataset);');
$plugins->attachHook('session_started', 'floodlight_add_js_page();');
$plugins->attachHook('common_post', 'floodlight_intercept_search();');
$plugins->attachHook('compile_template', 'floodlight_add_js();');

function floodlight_inject_searchflags(&$return)
{
  if ( strstr($return[0], '<input name="q"') )
  {
    $hackme =& $return[0];
  }
  else if ( strstr($return[1], '<input name="q"') )
  {
    $hackme =& $return[1];
  }
  else
  {
    return;
  }
  $hackme = str_replace('<input name="q"', '<input name="q" autocomplete="off" onkeyup="this.setAttribute(\'autocomplete\', \'off\'); this.onkeyup = null; this.className = \'autofill floodlight\'; autofill_init_element(this, {});"', $hackme);
}

function floodlight_perform_search(&$dataset)
{
  global $db, $session, $paths, $template, $plugins; // Common objects
  if ( $_GET['type'] == 'floodlight' )
  {
    $results = perform_search($_GET['userinput'], $warnings, false, $word_list);
    if ( count($results) > 5 )
    {
      $results = array_slice($results, 0, 5);
    }
    foreach ( $results as $result )
    {
      $dataset[] = array(
          0 => "go:{$paths->nslist[$result['namespace']]}{$result['page_id']}",
          'title' => str_replace(array('<highlight>', '</highlight>'), array('<b>', '</b>'), $result['page_name']),
          'score' => $result['score'],
          'type' => $result['page_note'],
          'size' => $result['page_length'],
        );
    }
  }
}

function floodlight_add_js_page()
{
  global $db, $session, $paths, $template, $plugins; // Common objects
  $paths->add_page(array(
      'name' => 'Floodlight Javascript',
      'urlname' => 'FloodlightJS',
      'namespace' => 'Special',
      'visible' => 0,
      'protected' => 0,
      'comments_on' => 0,
      'special' => 0
    ));
}

function floodlight_intercept_search()
{
  global $db, $session, $paths, $template, $plugins; // Common objects
  if ( $paths->page_id == 'Search' && $paths->namespace == 'Special' )
  {
    if ( isset($_GET['q']) && preg_match('/^go:/', $_GET['q']) )
    {
      redirect(makeUrl(preg_replace('/^go:/', '', $_GET['q'])), '', '', 0);
    }
  }
}

function floodlight_add_js()
{
  global $db, $session, $paths, $template, $plugins; // Common objects
  $template->add_header('<script type="text/javascript" src="' . makeUrlNS('Special', 'FloodlightJS', false, true) . '"></script>');
  if ( method_exists($template, 'preload_js') )
  {
    $template->preload_js(array('l10n', 'jquery', 'jquery-ui', 'autofill'));
  }
}

function page_Special_FloodlightJS()
{
  global $db, $session, $paths, $template, $plugins; // Common objects
  header('Content-type: text/javascript');
  header('ETag: ' . sha1(__FILE__));
  
  global $aggressive_optimize_html;
  $aggressive_optimize_html = false;
  
  echo <<<EOF
var autofill_schemas = window.autofill_schemas || {};
autofill_schemas.floodlight = {
  init: function(element, fillclass, params)
  {
    params = params || {};
    $(element).autocomplete(makeUrlNS('Special', 'Autofill', 'type=' + fillclass) + '&userinput=', {
        minChars: 3,
        formatItem: function(row, _, __)
        {
          var type = ( typeof(row.type) == 'string' ) ? row.type : '';
          var html = '<big>' + row.title + '</big> <small>' + type + '</small>';
          html += '<br /><small>' + \$lang.get('floodlight_lbl_score') + row.score + '% | ' + row.size + '</small>';
          return html;
        },
        tableHeader: '<tr><th>' + \$lang.get('floodlight_table_heading') + '</th></tr>',
        showWhenNoResults: true,
        onItemSelect: function(li)
        {
          window.location = makeUrl(li.selectValue.replace(/^go:/, ''));
        },
        width: 180,
        noResultsHTML: '<tr><td class="row1" style="font-size: smaller;">' + \$lang.get('floodlight_msg_no_results') + '</td></tr>',
    });
  }
};

function AutofillFloodlight(el, p)
{
  p = p || {};
  var cn_append = ( el.className ) ? ' ' + el.className : '';
  el.className = 'autofill floodlight' + cn_append;
  el.onkeyup = null;
  autofill_init_element(el, p);
}

addOnloadHook(function()
  {
    if ( document.forms[0] && document.forms[0].q )
    {
      document.forms[0].q.onkeyup = function() {
        new AutofillFloodlight(this);
      };
    }
  });
EOF;
}

/**!language**

The following text up to the closing comment tag is JSON language data.
It is not PHP code but your editor or IDE may highlight it as such. This
data is imported when the plugin is loaded for the first time; it provides
the strings displayed by this plugin's interface.

You should copy and paste this block when you create your own plugins so
that these comments and the basic structure of the language data is
preserved. All language data is in the same format as the Enano core
language files in the /language/* directories. See the Enano Localization
Guide and Enano API Documentation for further information on the format of
language files.

The exception in plugin language file format is that multiple languages
may be specified in the language block. This should be done by way of making
the top-level elements each a JSON language object, with elements named
according to the ISO-639-1 language they are representing. The path should be:

  root => language ID => categories array, ( strings object => category \
  objects => strings )

All text leading up to first curly brace is stripped by the parser; using
a code tag makes jEdit and other editors do automatic indentation and
syntax highlighting on the language data. The use of the code tag is not
necessary; it is only included as a tool for development.

<code>
{
  // english
  eng: {
    categories: [ 'meta', 'floodlight' ],
    strings: {
      meta: {
        floodlight: 'Floodlight plugin'
      },
      floodlight: {
        table_heading: 'Search results',
        msg_no_results: 'No results',
        lbl_score: 'Relevance: ',
      }
    }
  }
}
</code>
**!*/
