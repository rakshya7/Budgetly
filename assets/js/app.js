document.addEventListener("DOMContentLoaded", () => {

    const typeSelect = document.getElementById("type");
    const categorySelect = document.getElementById("category_id");

    if (!typeSelect || !categorySelect) {
        return;
    }

    const options = Array.from(categorySelect.options);

    function filterCategories() {

        const selectedType = typeSelect.value;

        options.forEach(option => {

            if (!option.value) {
                option.hidden = false;
                return;
            }

            option.hidden =
                option.dataset.type !== selectedType;

        });

        /*
         * If the currently selected category
         * doesn't belong to the selected type,
         * reset the category.
         */

        const selectedOption =
            categorySelect.options[categorySelect.selectedIndex];

        if (
            selectedOption &&
            selectedOption.value &&
            selectedOption.dataset.type !== selectedType
        ) {
            categorySelect.value = "";
        }
    }

    typeSelect.addEventListener("change", filterCategories);

    filterCategories();

});