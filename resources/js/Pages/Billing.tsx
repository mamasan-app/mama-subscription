import { ShoppingCart } from "@/components/ShoppingCart";
import { Button } from "@/components/ui/button";
import { useNavigate } from "react-router-dom";

const Billing = () => {
  const navigate = useNavigate();

  return (
    <div className="container mx-auto px-4 py-8">
      <div className="flex justify-between items-center mb-8">
        <h1 className="text-3xl font-bold">Facturación</h1>
        <Button variant="outline" onClick={() => navigate(-1)}>
          Volver
        </Button>
      </div>
      <ShoppingCart />
    </div>
  );
};

export default Billing;