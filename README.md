# PHP Composer Package of Salesforce Web-To-Lead Submitter

To use you just need static `submit` function which takes to arguments.

```php

public static function submit($data, $w2l_file)

```

`$data` is the data you want to submit to salesforce.com through their
web-to-lead form

`$w2l_file` is the path to the web-to-lead HTML file downloaded from 
salesforce.com when generating Web-To-Lead.

The library will parse the HTML file and generate the structure of the
data that SF accepts.

After it normalizes the data based on the structure, cleans up empty 
fields and decodes for custom fields with tokens in `name=` attribute.

And after all that it will POST the form to the URL in the web-to-lead
HTML file form `action=` attribute.
