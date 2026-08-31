function uploadFiles(files)
{
    let form = new FormData();
    form.append('count', files.length);

    for (let i = 0; i < files.length; i++)
    {
        form.append('file' + i, files[i]);
    }

    return new Promise(function(resolve, reject)
    {
        streamServerEvents('/files/upload', {
            method: 'POST',
            payload: form
        }, function(data, server)
        {
            console.log(new Date().toTimeString(), data);
    
            if (data.includes('complete'))
            {
                console.log('Stream closed.');
                server.close();
                resolve({
                    'name': 'john',
                    'text': 'hello world'
                });
            }
        });
    });
}

function selectFiles()
{    
    let input = document.createElement('input');
    input.type = 'file';
    input.multiple = true;
    document.body.appendChild(input);

    return new Promise(function(resolve, reject)
    {
        input.click();

        input.addEventListener('cancel', () => reject('canceled'));
        input.addEventListener('change', function()
        {
            if (input.files.length == 0) return;
            uploadFiles(input.files).then((locations) => resolve(locations));
        });

        document.body.removeChild(input);
    });
}