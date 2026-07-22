let form = Array.from(document.querySelectorAll("[name]"));

function submit()
{
    let vals = form.map(function(input)
    {
        return [
            input.getAttribute("name"),
            input.getAttribute("type") == "checkbox" ? input.checked : input.value
        ];
    });

    console.table(vals.map(function(item)
    {
        return {
            key: item[0],
            value: item[1]
        };
    }));
}

document.getElementById("debug-button").onclick = submit;
submit();