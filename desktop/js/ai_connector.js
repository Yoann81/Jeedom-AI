/* Fonction pour ajouter un équipement (C'est ce qui répare votre bouton Ajouter) */
$('.eqLogicAction[data-action=add]').on('click', function () {
    jeedom.eqLogic.save({
        type: 'ai_connector',
        onSuccess: function (id) {
            location.href = 'index.php?v=d&m=ai_connector&p=ai_connector&id=' + id;
        }
    });
});

/* Fonction pour ouvrir un équipement existant quand on clique sur sa carte */
$('.eqLogicDisplayCard').on('click', function () {
    location.href = 'index.php?v=d&m=ai_connector&p=ai_connector&id=' + $(this).attr('data-eqLogic_id');
});

/* Permet la réorganisation des commandes au glisser-déposer */
$("#table_cmd").sortable({
  axis: "y",
  cursor: "move",
  items: ".cmd",
  placeholder: "ui-state-highlight",
  tolerance: "intersect",
  forcePlaceholderSize: true
});
/* Fonction pour le bouton de sélection de commande (Modal Jeedom) */
$(document).on('click', '.bt_selectCmdExpression', function () {
    var _el = $(this).closest('.input-group').find('.configKey');
    jeedom.cmd.getSelectModal({
        resPanel: {
            type: 'action',
            subType: 'message'
        }
    }, function (_result) {
        _el.value(_result.human);
    });
});
/* Votre fonction d'affichage des commandes (mise à jour) */
function addCmdToTable(_cmd) {
  if (!isset(_cmd)) {
    var _cmd = { configuration: {} }
  }
  if (!isset(_cmd.configuration)) {
    _cmd.configuration = {}
  }
  var tr = '<tr class="cmd" data-cmd_id="' + init(_cmd.id) + '">'
  tr += '<td class="hidden-xs">'
  tr += '<span class="cmdAttr" data-l1key="id"></span>'
  tr += '</td>'
  tr += '<td>'
  tr += '<div class="input-group">'
  tr += '<input class="cmdAttr form-control input-sm roundedLeft" data-l1key="name" placeholder="{{Nom de la commande}}">'
  tr += '<span class="input-group-btn"><a class="cmdAction btn btn-sm btn-default" data-l1key="chooseIcon" title="{{Choisir une icône}}"><i class="fas fa-icons"></i></a></span>'
  tr += '<span class="cmdAttr input-group-addon roundedRight" data-l1key="display" data-l2key="icon" style="font-size:19px;padding:0 5px 0 0!important;"></span>'
  tr += '</div>'
  tr += '</td>'
  tr += '<td>'
  tr += '<span class="type" type="' + init(_cmd.type) + '">' + jeedom.cmd.availableType() + '</span>'
  tr += '<span class="subType" subType="' + init(_cmd.subType) + '"></span>'
  tr += '</td>'
  tr += '<td>'
  tr += '<label class="checkbox-inline"><input type="checkbox" class="cmdAttr" data-l1key="isVisible" checked/>{{Afficher}}</label> '
  tr += '</td>'
  tr += '<td>'
  if (is_numeric(_cmd.id)) {
    tr += '<a class="btn btn-default btn-xs cmdAction" data-action="configure"><i class="fas fa-cogs"></i></a> '
    tr += '<a class="btn btn-default btn-xs cmdAction" data-action="test"><i class="fas fa-rss"></i> {{Tester}}</a>'
  }
  tr += '<i class="fas fa-minus-circle pull-right cmdAction cursor" data-action="remove" title="{{Supprimer la commande}}"></i></td>'
  tr += '</tr>'
  
  $('#table_cmd tbody').append(tr)
  var tr = $('#table_cmd tbody tr').last()
  tr.setValues(_cmd, '.cmdAttr')
  jeedom.cmd.changeType(tr, init(_cmd.subType))
}