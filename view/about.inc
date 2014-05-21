{# This is the template for the about page. You can enter any html here and show different language versions by using the lang value in a twig conditional as demonstrated below. #}
{% if lang == 'fi' %}

<h4>Tietoja sanastoselaimesta</h4>
<p>ONKI Light on web-pohjainen sanasto ja ontologiaselain.</p>
<a href="http://code.google.com/p/onki-light/">ONKI Light Google Code -repositorio</a>
{% elseif lang == 'sv' %}

<h4>Information</h4>
<p>ONKI Light är en ontologibrowser.</p>
<a href="http://code.google.com/p/onki-light/">ONKI Light på Google Code</a>

{% elseif lang == 'en' %}

<h4>About</h4>
<p>ONKI Light is a web based open source ontology browser.</p>
<a href="http://code.google.com/p/onki-light/">ONKI Light Google Code -repository</a>

{% endif %}
