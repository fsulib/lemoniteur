#!/usr/bin/env php
<?php

$baseurl = "http://diginole.lib.fsu.edu/";

shell_exec('rm -rf output');
shell_exec('mkdir output');

$inarg = $argv[1];
$infile = fopen("{$inarg}", "r");
$done = fopen("output/done.txt", "w");
while ($line = fgets($infile)) {
  list($issue_pid, $filename) = explode(',', trim($line));
  echo "Gathering pages for {$issue_pid} - {$filename}...\n";
  shell_exec("mkdir output/{$filename}");
  $pages_html = file_get_contents("{$baseurl}/islandora/object/{$issue_pid}/issue_pages");
  preg_match_all('/dt\ class="islandora-object-thumb"><a\ href="\/islandora\/object\/(fsu%3A\d*)"/', $pages_html, $pages);
  $i = 1;
  foreach ($pages[1] as $page_pid) {
    $page_pid = str_replace('%3A', ':', $page_pid);
    echo "Gathering {$filename} PDF #{$i}: {$page_pid}...\n";
    shell_exec("wget {$baseurl}/islandora/object/{$page_pid}/datastream/PDF/download -O output/{$filename}/{$i}.pdf");
    $i++;
  }
  echo "Trimming extraneous extra pages...\n";
  $pages_files = array_slice(scandir("output/{$filename}"), 2);
  $pathed_pages = [];
  $pathed_trimmed_pages = [];
  foreach ($pages_files as $page_file) {
    shell_exec("pdftk output/{$filename}/{$page_file} cat 1 output output/{$filename}/trimmed.{$page_file}");
    $pathed_pages[] = "output/{$filename}/{$page_file}";
    $pathed_trimmed_pages[] = "output/{$filename}/trimmed.{$page_file}";
  }
  echo "Combining {$filename} pages into single issue PDF...\n";
  $pages_string = implode($pathed_pages, ' ');
  $trimmed_pages_string = implode($pathed_trimmed_pages, ' ');
  shell_exec("pdftk {$trimmed_pages_string} cat output output/{$filename}.pdf");
  shell_exec("rm -rf output/{$filename}");
  echo "output/{$filename}.pdf complete.\n\n\n";

  fwrite($done, $line);
}

echo "Harvest complete.\n";
fclose($infile);
fclose($done);
