window.deleteItem = function (id, route) {
    console.log(route);
    Swal.fire({
        title: "Are you sure?",
        text: "You won't be able to revert this!",
        icon: "warning",
        showCancelButton: true,
        confirmButtonColor: "#3085d6",
        cancelButtonColor: "#d33",
        confirmButtonText: "Yes, delete it!",
    }).then((result) => {
        if (result.isConfirmed) {
            axios
                .delete(route, {
                    headers: {
                        "X-CSRF-TOKEN": document
                            .querySelector('meta[name="csrf-token"]')
                            .getAttribute("content"),
                    },
                })
                .then((response) => {
                    Swal.fire(
                        "Deleted!",
                        response.data.message,
                        "success",
                    ).then(() => {
                        location.reload();
                    });
                })
                .catch((error) => {
                    Swal.fire(
                        "Error!",
                        "An error occurred while deleting the item.",
                        "error",
                    );
                });
        }
    });
};
