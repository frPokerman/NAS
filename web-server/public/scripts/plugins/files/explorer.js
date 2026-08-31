window.addEventListener('keydown', function(e)
{
    if (e.code === 'KeyO' && e.ctrlKey)
    {
        e.preventDefault();
        if (!e.repeat) selectFiles();
    }
});