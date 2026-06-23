<?php

$resend = Resend::client('re_esV8qrib_3L1LaajT2gXjHxC55FSWXSSz');

$resend->emails->send([
  'from' => 'onboarding@resend.dev',
  'to' => 'futurexkorat@gmail.com',
  'subject' => 'Hello World',
  'html' => '<p>Congrats on sending your <strong>first email</strong>!</p>'
]);