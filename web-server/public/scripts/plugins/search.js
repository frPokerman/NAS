// Quickview meta params:
//  snippet (mandatory)
//  action
//  color
//  class

class SearchItem
{
    constructor(text, category = '', icon = '', ...meta)
    {
        this.text = text;
        this.category = category;
        this.icon = icon;
        this.meta = meta;
    }

    static fromJSON(json)
    {
        /**
            {
                "text": "AcesToAces - memory lane",
                "category": "Sounds",
                "icon": "/icons/plugins/sounds.svg",
                "meta": {
                    {
                        "snippet": "Play",
                        // "type": "action",
                        "action": "/sounds/play?m=aff0e6d2"
                    },
                    {
                        "snippet": "Queue",
                        "action": "/sounds/queue?m=aff0e6d2"
                    },
                    {
                        "snippet": "2:16",
                        // "type": "info"
                    }
                }
            }
        **/

        let obj = JSON.parse(json);
        return new SearchItem(obj.text, obj.category, obj.icon, ...obj.meta);
    }
}

async function SearchBar_Search(search, delta)
{
    console.log('Default search: "' + search + '" (' + delta + ' ms)');

    return search.split(' ').filter((word) => word?.length).map((word) => new SearchItem(
        word[0].toUpperCase() + word.substring(1), 'Demo', null,
        { snippet: 'Hello' },
        { snippet: 'World' },
        { snippet: 'Click', action: () =>
        {
            fetch('/test/test1')
            .then((res) => res.text())
            .then((text) => alert(text));
        } }
    ));
}

function SearchBar_Select(item)
{
    console.log('Default (search) selection: "' + item.text + '".');
}

window.addEventListener('DOMContentLoaded', function()
{
    const search = document.querySelector('[component=search_bar] input');
    const results_section = document.querySelector('[component=search_results]');
    const result_template = document.getElementById('search_result_template');
    const quickview_template = document.getElementById('search_quickview_label_template');

    if (!search)
    {
        delete SearchBar_Search;
        console.warn(
            'No search bar found!',
            'Did you import the search bar properly?',
            'You should not import this script manually, without importing the search bar.'
        );
        return;
    }

    const context = {
        value: '',
        elapsed: 0,
        callback: 0,
        async assert_delay(delay)
        {
            if (this.callback !== 0)
            {
                clearTimeout(this.callback);
                this.callback = 0;
            }

            if (this.elapsed >= delay) return this.value;
            else return new Promise((resolve) =>
            {
                this.callback = setTimeout(() => resolve(this.value), delay);
            });
        }
    };

    function clear_results()
    {
        results_section.innerHTML = '';
    }

    function append_result(...results)
    {
        let clone;
        let element;
        let child;

        for (let result of results)
        {
            clone = document.importNode(result_template.content, true);

            element = clone.getElementById('search_result_title');
            element.innerHTML = result.text;
            element.removeAttribute('id');

            if (result.category)
            {
                element = clone.getElementById('search_result_category');
                element.innerHTML = result.category;
                element.removeAttribute('id');
            }

            if (result.meta)
            {
                element = clone.getElementById('search_result_quickview');

                for (let meta of result.meta)
                {
                    child = document.importNode(quickview_template.content, true).querySelector('span');
                    child.innerHTML = meta.snippet;

                    if (meta.action)
                    {
                        child.classList.add('clickable');
                        child.addEventListener('click', (e) =>
                        {
                            meta.action(result, meta);
                            e.stopPropagation();
                        });
                    }

                    if (meta.color) child.style.backgroundColor = meta.color;
                    else if (meta.action)
                    {
                        child.classList.remove('contrast');
                        child.classList.add('btn');
                    }

                    if (meta.class) meta.class.split(' ').forEach((c) => child.classList.add(c));
                    
                    element.appendChild(child);
                }

                element.removeAttribute('id');
            }
            
            results_section.appendChild(clone);
            results_section.lastElementChild.addEventListener('click', (e) => SearchBar_Select(result));
        }
    }

    let time = new Date().getTime();
    let post_time = 0;
    search.addEventListener('input', function()
    {
        if (search.value.trim() == context.value) return;

        if (search.value)
        {
            post_time = new Date().getTime();
            context.value = search.value;
            context.elapsed = post_time - time;
            SearchBar_Search.call(context, search.value, context.elapsed).then((result) =>
            {
                clear_results();
                if (result?.length) append_result(...result);
            });
            time = post_time;
        }
        else
        {
            clear_results();
            time = new Date().getTime();
        }
    })
});