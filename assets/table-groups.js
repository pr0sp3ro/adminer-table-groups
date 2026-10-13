(() => {
    'use strict';

    const list = document.querySelector('#tables[data-groups-config]');

    if (!list) {
        return;
    }

    const config = JSON.parse(list.dataset.groupsConfig);
    const labels = config.labels;
    const key = 'adminer-table-groups:' + JSON.stringify([location.pathname, ...config.context]);
    const compare = new Intl.Collator(undefined, {numeric: true, sensitivity: 'base'}).compare;
    const objects = Array.from(list.children).sort((a, b) =>
        Number(a.dataset.view) - Number(b.dataset.view) || compare(a.dataset.tableName, b.dataset.tableName)
    );
    let state = {version: 1, groups: [], assignments: Object.create(null)};
    let warning = '';

    try {
        const saved = localStorage.getItem(key);

        if (saved !== null) {
            const parsed = JSON.parse(saved);
            const ids = new Set();
            const names = new Set();

            if (!parsed || parsed.version !== 1 || !Array.isArray(parsed.groups)
                || !parsed.assignments || typeof parsed.assignments !== 'object' || Array.isArray(parsed.assignments)) {
                throw new Error('Invalid groups');
            }

            for (const group of parsed.groups) {
                if (!group || typeof group.id !== 'string' || !group.id
                    || typeof group.name !== 'string' || !group.name.trim()
                    || ids.has(group.id) || names.has(group.name.trim().toLocaleLowerCase())) {
                    throw new Error('Invalid group');
                }

                ids.add(group.id);
                names.add(group.name.trim().toLocaleLowerCase());
                group.name = group.name.trim();
            }

            if (Object.values(parsed.assignments).some(id => typeof id !== 'string' || !ids.has(id))) {
                throw new Error('Invalid assignments');
            }

            state = {version: 1, groups: parsed.groups, assignments: Object.assign(Object.create(null), parsed.assignments)};
        }
    } catch (error) {
        warning = error instanceof SyntaxError || error.message.startsWith('Invalid')
            ? labels.invalidStorage : labels.storageUnavailable;
    }

    function element(tag, text, className) {
        const node = document.createElement(tag);
        node.textContent = text;

        if (className) {
            node.className = className;
        }

        return node;
    }

    const panel = document.createElement('details');
    panel.className = 'table-groups-manager';
    panel.append(element('summary', labels.manage));
    const controls = document.createElement('div');
    panel.append(controls);
    const notice = element('p', warning, 'table-groups-notice');
    notice.setAttribute('role', 'status');
    list.before(panel, notice);

    function sortedGroups() {
        return state.groups.slice().sort((a, b) => compare(a.name, b.name));
    }

    function save() {
        try {
            localStorage.setItem(key, JSON.stringify(state));
            warning = '';
        } catch (error) {
            warning = labels.storageUnavailable;
        }

        notice.textContent = warning;
        render();
    }

    function validName(name, currentId) {
        return name !== '' && !state.groups.some(group =>
            group.id !== currentId && group.name.toLocaleLowerCase() === name.toLocaleLowerCase()
        );
    }

    function button(text, action) {
        const node = element('button', text);
        node.type = 'button';
        node.addEventListener('click', action);
        return node;
    }

    function groupEditor(group) {
        const row = element('div', '', 'table-groups-editor');
        const label = element('label', group ? group.name : labels.newGroup);
        const input = document.createElement('input');
        input.type = 'text';
        input.value = group ? group.name : '';
        input.setAttribute('aria-label', labels.groupName);
        label.append(input);
        const commit = () => {
            const name = input.value.trim();
            input.setCustomValidity(validName(name, group && group.id) ? '' : labels.invalidName);

            if (!input.reportValidity()) {
                return;
            }

            if (group) {
                group.name = name;
            } else {
                // IDs remain stable when a group is renamed.
                state.groups.push({id: Array.from(crypto.getRandomValues(new Uint8Array(16)), byte => byte.toString(16).padStart(2, '0')).join(''), name});
            }

            save();
        };
        input.addEventListener('input', () => input.setCustomValidity(''));
        input.addEventListener('keydown', event => {
            if (event.key === 'Enter') {
                event.preventDefault();
                commit();
            }
        });
        row.append(label, button(group ? labels.rename : labels.create, commit));

        if (group) {
            row.append(button(labels.delete, () => {
                if (!confirm(labels.confirmDelete)) {
                    return;
                }

                state.groups = state.groups.filter(item => item.id !== group.id);

                for (const [name, id] of Object.entries(state.assignments)) {
                    if (id === group.id) {
                        delete state.assignments[name];
                    }
                }

                save();
            }));
        }

        return row;
    }

    function render() {
        const groups = sortedGroups();
        controls.replaceChildren(groupEditor(null));

        for (const group of groups) {
            controls.append(groupEditor(group));
        }

        const assignments = element('div', '', 'table-groups-assignments');

        for (const object of objects) {
            const name = object.dataset.tableName;
            const label = element('label', name);
            const select = document.createElement('select');
            select.append(new Option(labels.noGroup, ''));

            for (const group of groups) {
                select.append(new Option(group.name, group.id));
            }

            select.value = state.assignments[name] || '';
            select.addEventListener('change', () => {
                const index = objects.indexOf(object);
                if (select.value) {
                    state.assignments[name] = select.value;
                } else {
                    delete state.assignments[name];
                }

                save();
                controls.querySelectorAll('select')[index].focus();
            });
            label.append(select);
            assignments.append(label);
        }

        controls.append(assignments);
        list.replaceChildren();

        for (const group of [...groups, {id: '', name: labels.other}]) {
            const members = objects.filter(object => (state.assignments[object.dataset.tableName] || '') === group.id);

            if (!group.id && members.length === 0) {
                continue;
            }

            const item = element('li', '', 'table-groups-group');
            const details = document.createElement('details');
            details.open = !group.id || members.some(object => object.querySelector('a.active'));
            const summary = document.createElement('summary');
            const views = members.filter(object => object.dataset.view === '1').length;
            const count = labels.tables + ': ' + (members.length - views)
                + (views ? ', ' + labels.views + ': ' + views : '');
            summary.append(element('span', group.name, 'table-groups-label'), element('span', count, 'table-groups-count'));
            const children = document.createElement('ul');
            children.append(...members);
            details.append(summary, children);
            item.append(details);
            list.append(item);
        }
    }

    render();
})();
