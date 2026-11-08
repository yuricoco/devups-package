{{--
    Seul endroit qui rend l'etat vide d'une datatable, appele par
    Datatable::render() (page complete) et Datatable::getTableRest()
    (reponse AJAX de recherche/pagination/tri).
--}}
<tr class="dv_empty_state">
    <td colspan="{{ $colspan }}" class="text-center text-muted" style="padding: 2rem 1rem;">
        @if ($searched)
            {{ t('Aucun résultat pour cette recherche.') }}
        @else
            {{ t('Aucun élément pour l\'instant.') }}
        @endif
    </td>
</tr>
