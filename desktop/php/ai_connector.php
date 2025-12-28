<?php
if (!is_object($plugin = plugin::byId('ai_connector'))) {
    throw new Exception(__('Plugin introuvable : ai_connector', __FILE__));
}
sendVarToJS('eqType', 'ai_connector');
$eqLogics = eqLogic::byType('ai_connector');
?>

<div class="row row-overflow">
    <div class="col-xs-12 eqLogicThumbnailDisplay">
        <legend><i class="fas fa-cog"></i> {{Gestion}}</legend>
        <div class="eqLogicAction cursor" data-action="add">
            <i class="fas fa-plus-circle"></i>
            <br>
            <span>{{Ajouter}}</span>
        </div>
        <div class="eqLogicAction cursor" data-action="gotoPluginConf">
            <i class="fas fa-wrench"></i>
            <br>
            <span>{{Configuration}}</span>
        </div>

        <legend><i class="fas fa-robot"></i> {{Mes IA}}</legend>
        <div class="eqLogicThumbnailContainer">
            <?php
            foreach ($eqLogics as $eqLogic) {
                $opacity = ($eqLogic->getIsEnable()) ? '' : 'disableCard';
                echo '<div class="eqLogicDisplayCard cursor ' . $opacity . '" data-eqLogic_id="' . $eqLogic->getId() . '">';
                echo '<img src="' . $plugin->getPathImgIcon() . '">';
                echo '<br>';
                echo '<span class="name">' . $eqLogic->getHumanName(true, true) . '</span>';
                echo '</div>';
            }
            ?>
        </div>
    </div>

    <div class="col-xs-12 eqLogic" style="display: none;">
        <div class="input-group pull-right" style="display:inline-flex">
            <span class="input-group-btn">
                <a class="btn btn-default btn-sm eqLogicAction" data-action="configure"><i class="fa fa-cogs"></i> {{Configuration avancée}}</a>
                <a class="btn btn-sm btn-success eqLogicAction" data-action="save"><i class="fas fa-check-circle"></i> {{Sauvegarder}}</a>
                <a class="btn btn-danger btn-sm eqLogicAction" data-action="remove"><i class="fas fa-minus-circle"></i> {{Supprimer}}</a>
            </span>
        </div>
        
        <ul class="nav nav-tabs" role="tablist">
            <li role="presentation"><a href="#" class="eqLogicAction" aria-controls="home" role="tab" data-toggle="tab" data-action="returnToThumbnailDisplay"><i class="fa fa-arrow-circle-left"></i></a></li>
            <li role="presentation" class="active"><a href="#eqlogictab" aria-controls="home" role="tab" data-toggle="tab"><i class="fas fa-tachometer-alt"></i> {{Equipement}}</a></li>
            <li role="presentation"><a href="#commandtab" aria-controls="profile" role="tab" data-toggle="tab"><i class="fas fa-list-alt"></i> {{Commandes}}</a></li>
        </ul>

        <div class="tab-content" style="height:calc(100% - 50px);overflow:auto;display:overflow:visible">
            <div role="tabpanel" class="tab-pane active" id="eqlogictab">
                <br/>
                <form class="form-horizontal">
                    <fieldset>
                        <div class="form-group">
                            <label class="col-sm-3 control-label">{{Nom de l'équipement}}</label>
                            <div class="col-sm-3">
                                <input type="text" class="eqLogicAttr form-control" data-l1key="id" style="display : none;" />
                                <input type="text" class="eqLogicAttr form-control" data-l1key="name" placeholder="{{Nom de l'équipement}}"/>
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="col-sm-3 control-label">{{Objet parent}}</label>
                            <div class="col-sm-3">
                                <select id="sel_object" class="eqLogicAttr form-control" data-l1key="object_id">
                                    <option value="">{{Aucun}}</option>
                                    <?php
                                    foreach (jeeObject::all() as $object) {
                                        echo '<option value="' . $object->getId() . '">' . $object->getName() . '</option>';
                                    }
                                    ?>
                                </select>
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="col-sm-3 control-label">{{Options}}</label>
                            <div class="col-sm-9">
                                <label class="checkbox-inline"><input type="checkbox" class="eqLogicAttr" data-l1key="isEnable" checked/>{{Activer}}</label>
                                <label class="checkbox-inline"><input type="checkbox" class="eqLogicAttr" data-l1key="isVisible" checked/>{{Visible}}</label>
                            </div>
                        </div>

                        <br>
                        <legend><i class="fas fa-microphone"></i> {{Configuration Vocale}}</legend>
                        <div class="form-group">
                            <label class="col-sm-3 control-label">{{Activer l'écoute}}</label>
                            <div class="col-sm-3">
                                <label class="checkbox-inline"><input type="checkbox" class="eqLogicAttr" data-l1key="configuration" data-l2key="voice_enable" />{{Activer}}</label>
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="col-sm-3 control-label">{{Commande de retour (HP)}}</label>
                            <div class="col-sm-4">
                                <div class="input-group">
                                    <input type="text" class="eqLogicAttr form-control" data-l1key="configuration" data-l2key="voice_cmd_id" />
                                    <span class="input-group-btn">
                                        <a class="btn btn-default bt_selectCmdExpression"><i class="fas fa-list-alt"></i></a>
                                    </span>
                                </div>
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="col-sm-3 control-label">{{ID Micro (Index)}}</label>
                            <div class="col-sm-2">
                                <input type="text" class="eqLogicAttr form-control" data-l1key="configuration" data-l2key="voice_device_id" placeholder="1"/>
                            </div>
                        </div>

                        <br>
                        <legend><i class="fas fa-key"></i> {{Paramètres de l'IA}}</legend>
                        <div class="form-group">
                            <label class="col-sm-3 control-label">{{Moteur d'IA}}</label>
                            <div class="col-sm-3">
                                <select class="eqLogicAttr form-control" data-l1key="configuration" data-l2key="engine">
                                    <option value="gemini">{{Google Gemini}}</option>
                                    <option value="openai">{{OpenAI (ChatGPT)}}</option>
                                    <option value="mistral">{{Mistral AI}}</option>
                                </select>
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="col-sm-3 control-label">{{Clé API}}</label>
                            <div class="col-sm-5">
                                <input type="password" class="eqLogicAttr form-control" data-l1key="configuration" data-l2key="apiKey" placeholder="{{Votre clé secrète}}"/>
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="col-sm-3 control-label">{{Modèle}}</label>
                            <div class="col-sm-3">
                                <input type="text" class="eqLogicAttr form-control" data-l1key="configuration" data-l2key="model" placeholder="ex: gemini-1.5-flash"/>
                            </div>
                        </div>
                    </fieldset>
                </form>
            </div>
            
            <div role="tabpanel" class="tab-pane" id="commandtab">
                <br/>
                <table id="table_cmd" class="table table-bordered table-condensed">
                    <thead>
                        <tr>
                            <th>{{Nom}}</th><th>{{Options}}</th><th>{{Action}}</th>
                        </tr>
                    </thead>
                    <tbody>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php include_file('desktop', 'ai_connector', 'js', 'ai_connector'); ?>
<?php include_file('core', 'plugin.template', 'js'); ?>