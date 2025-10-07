export function prepareModal() {
    document.getElementById('myModal').style.display = "block";
    const container = document.getElementById('modalFormContainer');
    container.innerHTML = '';
    return container;
}

export function closeModal() {
    document.getElementById('modalFormContainer').innerHTML = '';
    document.getElementById('myModal').style.display = 'none';
}
