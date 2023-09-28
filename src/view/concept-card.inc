<div class="col-md-8 ps-3">
  <div class="pt-3" id="main-content">

    <div class="main-content-section p-5">

      <div class="d-flex" id="term-heading">
        <span>{{ "skos:prefLabel" | trans }}</span>
        <h2 class="mb-0" id="pref-label">{{ concept.label }}</h2>
        <button class="btn btn-default copy-clipboard" type="button" data-bs-toggle="tooltip" data-bs-placement="button" title="{{ "Copy to clipboard" | trans }}" for="#pref-label">
          <i class="fa-regular fa-copy"></i>
        </button>
      </div>

      <table class="table" id="main-table">
        <tbody>
          <tr>
            <td class="main-table-label px-0">Tyyppi</td>
            <td class="align-middle px-0">Thsys</td>
          </tr>
          <tr>
            <td class="main-table-label px-0">Alakäsitteet</td>
            <td class="align-middle px-0">
              <a href="#">A.00 Allgemeinwörter</a><br>
              <a href="#">A.01 Regionaladjektive und Sprache</a>
            </td>
          </tr>
          <tr>
            <td class="main-table-label px-0">Notaatio</td>
            <td class="align-middle px-0">A</td>
          </tr>
          <tr>
            <td class="main-table-label px-0">Muunkieliset termit</td>
            <td class="term-other-languages align-middle px-0">
              <a href="#">A General descriptors</a>
              <span>englanti</span>
            </td>
          </tr>
          <tr>
            <td class="main-table-label px-0">URI</td>
            <td class="align-middle px-0"><a href="#">{{ concept.uri }}</a></td>
          </tr>
          <tr>
            <td class="main-table-label px-0"><i class="fa-solid fa-download"></i> {{ "Download this concept" | trans }}</td>
            <td class="align-middle px-0">
              <a class="me-3" href="rest/v1/{% if concept.vocab %}{{ concept.vocab.id }}{% else %}{{ vocab.id }}{% endif %}/data?uri={{ concept.uri|url_encode }}&amp;format=application/rdf%2Bxml">RDF/XML</a>
              <a class="me-3" href="rest/v1/{% if concept.vocab %}{{ concept.vocab.id }}{% else %}{{ vocab.id }}{% endif %}/data?uri={{ concept.uri|url_encode }}&amp;format=text/turtle">TURTLE</a>
              <a href="rest/v1/{% if concept.vocab %}{{ concept.vocab.id }}{% else %}{{ vocab.id }}{% endif %}/data?uri={{ concept.uri|url_encode }}&amp;format=application/ld%2Bjson">JSON-LD</a>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

  </div>
</div>
