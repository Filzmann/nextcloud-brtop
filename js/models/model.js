(function() {
    class Model {
        static get(data) {
            if (data == null) {
                return null;
            }

            return data instanceof this ? data : new this(data || {});
        }

        static get_all(items = []) {
            if (!Array.isArray(items)) {
                return [];
            }

            return items.map(item => this.get(item)).filter(Boolean);
        }

        toArray() {
            throw new Error(`${this.constructor.name} muss toArray implementieren.`);
        }

        to_array() {
            return this.toArray();
        }

        save() {
            throw new Error(`${this.constructor.name} kann nicht direkt gespeichert werden.`);
        }
    }

    window.BRTop = window.BRTop || {};
    window.BRTop.models = window.BRTop.models || {};
    window.BRTop.models.Model = Model;
})();
