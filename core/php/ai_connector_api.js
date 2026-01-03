/**
 * AI Connector - Interface JavaScript pour contrôler Jeedom via l'IA
 * 
 * Fournit des méthodes pratiques pour interagir avec les équipements IA
 * et récupérer/contrôler les équipements Jeedom
 */

class AiConnectorAPI {
    constructor() {
        this.baseUrl = 'plugins/ai_connector/core/ajax/ai_connector.ajax.php';
        this.equipments = [];
        this.currentContext = null;
    }

    /**
     * Effectue une requête AJAX
     * @param action - L'action à exécuter
     * @param data - Les données additionnelles
     * @returns Promise
     */
    async request(action, data = {}) {
        return new Promise((resolve, reject) => {
            const params = {
                type: 'POST',
                url: this.baseUrl,
                data: {action: action, ...data},
                global: false,
                error: function(error) {
                    reject(error);
                }
            };

            jeedom.ajax.loadData(params);
            
            // Attendre la réponse
            setTimeout(() => {
                jeedom.ajax.loadData({
                    ...params,
                    success: function(response) {
                        resolve(response);
                    }
                });
            }, 100);
        });
    }

    /**
     * Récupère tous les équipements
     * @returns Array des équipements
     */
    async getAllEquipments() {
        try {
            const response = await $.ajax({
                type: 'POST',
                url: this.baseUrl,
                data: {action: 'getAllEquipments'}
            });
            this.equipments = response;
            return response;
        } catch (error) {
            console.error('Erreur récupération équipements:', error);
            return [];
        }
    }

    /**
     * Récupère les commandes d'un équipement
     * @param eq_id - ID de l'équipement
     * @returns Array des commandes
     */
    async getEquipmentCommands(eq_id) {
        try {
            const response = await $.ajax({
                type: 'POST',
                url: this.baseUrl,
                data: {
                    action: 'getEquipmentCommands',
                    eq_id: eq_id
                }
            });
            return response;
        } catch (error) {
            console.error('Erreur récupération commandes:', error);
            return [];
        }
    }

    /**
     * Exécute une commande
     * @param cmd_id - ID de la commande
     * @param options - Options (ex: {value: 75})
     * @returns String le résultat
     */
    async executeCommand(cmd_id, options = {}) {
        try {
            const response = await $.ajax({
                type: 'POST',
                url: this.baseUrl,
                data: {
                    action: 'executeCommand',
                    cmd_id: cmd_id,
                    options: JSON.stringify(options)
                }
            });
            return response;
        } catch (error) {
            console.error('Erreur exécution commande:', error);
            return 'Erreur lors de l\'exécution';
        }
    }

    /**
     * Récupère le contexte IA formaté
     * @param eq_id - ID de l'équipement IA
     * @returns String le contexte
     */
    async getJeedomContext(eq_id) {
        try {
            const response = await $.ajax({
                type: 'POST',
                url: this.baseUrl,
                data: {
                    action: 'getJeedomContext',
                    eq_id: eq_id
                }
            });
            this.currentContext = response;
            return response;
        } catch (error) {
            console.error('Erreur récupération contexte:', error);
            return '';
        }
    }

    /**
     * Récupère tous les équipements avec leurs commandes
     * @returns Array équipements avec commandes imbriquées
     */
    async getAllEquipmentsWithCommands() {
        try {
            const response = await $.ajax({
                type: 'POST',
                url: this.baseUrl,
                data: {action: 'getAllEquipmentsWithCommands'}
            });
            return response;
        } catch (error) {
            console.error('Erreur récupération équipements+commandes:', error);
            return [];
        }
    }

    /**
     * Envoie un message à l'IA
     * @param eq_id - ID de l'équipement IA
     * @param message - Le message
     * @returns Promise avec la réponse de l'IA
     */
    async sendMessage(eq_id, message) {
        try {
            // Exécuter la commande 'ask'
            const eqLogic = jeedom.eqLogic.byId(eq_id);
            if (!eqLogic || eqLogic.type !== 'ai_connector') {
                throw new Error('Équipement IA non trouvé');
            }

            const cmd = eqLogic.getCmd(null, 'ask');
            if (!cmd) {
                throw new Error('Commande "ask" non trouvée');
            }

            return new Promise((resolve) => {
                jeedom.cmd.execute({
                    id: cmd.id,
                    value: message
                }, function() {
                    // Récupérer la réponse
                    setTimeout(() => {
                        const responseCmd = eqLogic.getCmd(null, 'reponse');
                        if (responseCmd) {
                            resolve(responseCmd.currentValue);
                        } else {
                            resolve('Aucune réponse');
                        }
                    }, 1000);
                });
            });
        } catch (error) {
            console.error('Erreur envoi message:', error);
            return 'Erreur: ' + error.message;
        }
    }

    /**
     * Cherche un équipement par nom
     * @param name - Nom ou partie du nom
     * @returns Objet équipement ou null
     */
    findEquipmentByName(name) {
        return this.equipments.find(eq => 
            eq.name.toLowerCase().includes(name.toLowerCase()) ||
            eq.humanName.toLowerCase().includes(name.toLowerCase())
        );
    }

    /**
     * Cherche une commande par nom dans un équipement
     * @param eq_id - ID de l'équipement
     * @param cmdName - Nom de la commande
     * @returns Object {equipment, command}
     */
    async findCommand(eq_id, cmdName) {
        const commands = await this.getEquipmentCommands(eq_id);
        const cmd = commands.find(c => 
            c.name.toLowerCase().includes(cmdName.toLowerCase())
        );
        return cmd ? {eq_id, command: cmd} : null;
    }

    /**
     * Liste tous les équipements dans la console
     */
    async listAllEquipments() {
        const eqs = await this.getAllEquipments();
        console.table(eqs.map(eq => ({
            ID: eq.id,
            Nom: eq.name,
            Type: eq.type,
            Activé: eq.isEnable ? '✓' : '✗'
        })));
        return eqs;
    }

    /**
     * Liste toutes les commandes d'un équipement
     * @param eq_id - ID de l'équipement
     */
    async listEquipmentCommands(eq_id) {
        const cmds = await this.getEquipmentCommands(eq_id);
        console.table(cmds.map(cmd => ({
            ID: cmd.id,
            Nom: cmd.name,
            Type: cmd.type,
            SubType: cmd.subType,
            Valeur: cmd.value
        })));
        return cmds;
    }

    /**
     * Exécute une action naturelle
     * @param action - Description naturelle (ex: "Allume le salon")
     * @param eq_id - ID de l'équipement IA
     */
    async executeNaturalAction(action, eq_id) {
        console.log('Action naturelle:', action);
        return this.sendMessage(eq_id, action);
    }
}

// Instanciation globale
const aiConnector = new AiConnectorAPI();

// Exemples d'utilisation
/*

// Récupérer tous les équipements
aiConnector.getAllEquipments().then(equipments => {
    console.log('Équipements:', equipments);
});

// Lister les commandes d'un équipement
aiConnector.listEquipmentCommands(5);

// Exécuter une commande
aiConnector.executeCommand(42).then(result => {
    console.log('Résultat:', result);
});

// Envoyer un message à l'IA
aiConnector.sendMessage(1, 'Allume la lumière du salon').then(response => {
    console.log('Réponse IA:', response);
});

// Afficher le contexte IA
aiConnector.getJeedomContext(1).then(context => {
    console.log('Contexte IA:\n', context);
});

*/
